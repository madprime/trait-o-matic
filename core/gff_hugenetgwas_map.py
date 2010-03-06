#!/usr/bin/python

"""
usage: %prog gff_file
"""

# Output HugeNET GWAS information in JSON format, if available
# ---
# This code is part of the Trait-o-matic project and is governed by its license.

import os, string, sys
import MySQLdb, MySQLdb.cursors
import simplejson as json
from utils import gff
from utils.biopython_utils import reverse_complement
from config import DB_HOST, HUGENET_USER, HUGENET_PASSWD, HUGENET_DATABASE

query = '''
SELECT
 concat(trait,' (',firstauthor,' in ',journal,' ',published_year,if(or_or_beta_is_or='Y',concat(', OR=',or_or_beta_ci),''),')'),
 pubmed_id,
 substring(substring_index(risk_allele_prevalence,concat(rsid,'-'),-1),1,1) risk_allele,
 strand
FROM hugenet_gwas
LEFT JOIN caliban.snp129 dbsnp ON dbsnp.name = hugenet_gwas.rsid
WHERE
rsid=%s AND ((strand="+" AND (risk_allele_prevalence like concat('%%',rsid,'-',%s) OR risk_allele_prevalence like concat('%%',rsid,'-',%s)))
 OR (strand="-" AND (risk_allele_prevalence like concat('%%',rsid,'-',%s) OR risk_allele_prevalence like concat('%%',rsid,'-',%s)))
 OR (risk_allele_prevalence like '%%-?%%'));
'''

def main():
	# return if we don't have the correct arguments
	if len(sys.argv) < 2:
		raise SystemExit(__doc__.replace("%prog", sys.argv[0]))
	
	# first, try to connect to the databases
	try:
		connection = MySQLdb.connect(host=DB_HOST, user=HUGENET_USER, passwd=HUGENET_PASSWD, db=HUGENET_DATABASE)
		cursor = connection.cursor()
	except MySQLdb.OperationalError, message:
		print "Error %d while connecting to database: %s" % (message[0], message[1])
		sys.exit()
	
	# doing this intersect operation speeds up our task significantly for full genomes
	gff_file = gff.input(sys.argv[1])
	for record in gff_file:
		# lightly parse to find the alleles and rs number
		alleles = record.attributes["alleles"].strip("\"").split("/")
		ref_allele = record.attributes["ref_allele"].strip("\"")
		try:
			xrefs = record.attributes["db_xref"].strip("\"").split(",")
		except KeyError:
			try:
				xrefs = record.attributes["Dbxref"].strip("\"").split(",")
			except KeyError:
				continue
		for x in xrefs:
			if x.startswith("dbsnp:rs"):
				rs = x.replace("dbsnp:", "")
				break
		
		# quit if we don't have an rs number
		if not rs:
			continue
		# we wouldn't know what to do with this, so pass it up for now
		if len(alleles) > 2:
			continue
		
		# create the genotype string from the given alleles
		#TODO: do something about the Y chromosome
		if len(alleles) == 1:
			genotype = alleles[0]
			alleles = (alleles[0], alleles[0])
		else:
			genotype = ';'.join(sorted(alleles))
		reverse_alleles = [reverse_complement(a) for a in alleles]
		
		# query the database
		cursor.execute(query, (rs,
				       alleles[0] + '%',
				       alleles[1] + '%',
				       reverse_alleles[0] + '%',
				       reverse_alleles[1] + '%'))
		data = cursor.fetchall()
		
		# move on if we don't have info
		if cursor.rowcount <= 0:
			continue

		gene_acid_base = None
		gene = None
		amino_acid_change_and_position = None
		try:
			gene_acid_base = record.attributes["amino_acid"].split("/")
		except KeyError:
			pass

		if gene_acid_base:
			# get amino acid change
			x = gene_acid_base[0].split(" ",1)
			gene = x[0]
			amino_acid_change_and_position = x[1]

		for d in data:
			trait = d[0]
			pubmed = d[1]
			trait_allele = d[2]
			strand = d[3]
			
			# format for output
			if record.start == record.end:
				coordinates = str(record.start)
			else:
				coordinates = str(record.start) + "-" + str(record.end)
			
			if pubmed != "":
				reference = "pmid:" + pubmed.replace(",", ",pmid:")
			else:
				reference = "dbsnp:" + rs

			if strand == '-' and trait_allele != '?':
				trait_allele = reverse_complement(trait_allele) + ' (' + trait_allele + '-)'

			output = {
				"chromosome": record.seqname,
				"coordinates": coordinates,
				"genotype": genotype,
				"variant": str(record),
				"phenotype": trait,
				"trait_allele": trait_allele,
				"reference": reference,
				"gene": gene,
				"amino_acid_change": amino_acid_change_and_position
			}
			print json.dumps(output)
	
	# close database cursor and connection
	cursor.close()
	connection.close()

if __name__ == "__main__":
	main()
