<?php
if (!isset($top_current_tab))
  $top_current_tab = "/results/";
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<div class="two-column">
				<div class="column">
<div class="portrait" style="float:right;"><img src="<?php echo $job['image']; ?>" width="100" height="100" alt="" /></div>
					<h3><?php echo htmlspecialchars(ereg_replace("\n.*","",$job['label'])); ?></h3>
					<p><?php echo nl2br(htmlspecialchars(ereg_replace("^[^\n]*\n?","",$job['label']))); ?></p>
<?php if (!(array_key_exists ('human', $job) && $job['human'])): ?>
<?php if (array_key_exists ('date-of-birth', $phenotypes)): ?>
					<p>Date of birth: <?php echo $phenotypes['date-of-birth']; ?><br>
<?php endif; ?>
<?php if (array_key_exists('sex', $phenotypes) && array_key_exists('ancestry', $phenotypes)): ?>
					<?php echo ucfirst(lang($phenotypes['sex'])); ?>, <?php function r($v, $w) { if ($v != '') { $v .= ', '; } $v .= lang($w); return $v; } print array_reduce($phenotypes['ancestry'], 'r'); ?></p>
<?php endif; ?>
<?php endif; ?>

<?php if ($this->config->item('enable_download_gff')): ?>
<p>Download:
<br />&rarr; <a href="/download/genotype/<?php echo urlencode($job_id); ?>">source data</a>
<?php if ($this->config->item('enable_download_dbsnp')): ?>
<br />&rarr; <a href="/download/dbsnp/<?php echo urlencode($job_id); ?>">source data + dbSNP IDs + reference alleles</a>
<?php endif; ?>
<?php if ($this->config->item('enable_download_nssnp')): ?>
<br />&rarr; <a href="/download/ns/<?php echo urlencode($job_id); ?>">nsSNPs</a>
<?php endif; ?>
<?php if ($this->config->item('enable_download_json')): ?>
<br />&rarr; <a href="/download/json/<?php echo urlencode($job_id); ?>">results in json format</a>
<?php endif; ?>
<?php endif; ?>

<?php
  $have_unshared = FALSE;
  foreach (array ("genotype", "coverage", "phenotype") as $kind):
    if (!isset($locator[$kind]))
      ;
    else if ($locator[$kind] == "") {
      print $public ? "" : "<br />Warehouse locator for $kind: N/A";
      $have_unshared = TRUE;
    } else
      print "<br />Warehouse locator for $kind: <a href=\"".$locator[$kind]."\">".preg_replace("{.*/([0-9a-f]{8})[0-9a-f]{24}.*}", "\$1...", $locator[$kind])."</a> (right-click to copy)";
  endforeach;

  if (!$public &&
      $job_public_mode >= 0 &&
      $this->config->item('enable_warehouse_storage') &&
      $have_unshared):
    print "<br /><a href=\"/share/".urlencode($job_id)."\">Copy data to warehouse</a>";
  endif;
?>
</p>
<?php
if (!$public):
$public_mode_strings = array(
	-1 => 'only you',
	0 => 'only you and expert curators',
	1 => 'everyone'
);
// these are just for show to express
// what it is that users, curators, and
// others may do at each of the three
// modes
//
// group = curators
// w = curate
// x = reprocess, etc.
$public_mode_symbols = array(
	-1 => '700',
	0 => '760',
	1 => '764'
);
$public_mode_actions = array(
	-1 => 'Restrict access to only me',
	0 => 'Restrict access to only me and expert curators',
	1 => 'Grant access to everyone (public sample)'
);
?>
					<p>Currently, <strong><?php echo htmlspecialchars($public_mode_strings[$job_public_mode]); ?></strong> may view these results<?php if($this->config->item('enable_chmod')): foreach($public_mode_actions as $k => $v): if ($job_public_mode != $k): ?><br><a href="/chmod/<?php echo urlencode($public_mode_symbols[$k]); ?>/<?php echo urlencode($job_id); ?>"><?php echo htmlspecialchars($v); ?></a><?php endif; endforeach; endif; ?></p>
					<p><a href="/reprocess/<?php echo urlencode($job_id); ?>" onclick="return window.confirm('Are you sure you want to discard current results and reprocess this query?')">Reprocess this query</a> &nbsp;&bull;&nbsp; <a href="/logout/">Log out</a></p>
<?php endif; ?>
				</div>
				<div class="last column">
					<p id="allele-frequency-legend" class="legend"><strong>Highlighting by allele frequency</strong><br>
					<span class="rare">Rare (<i>f</i>&nbsp;&lt;&nbsp;0.05)</span><br>
					<span class="minor">Minor (0.05&nbsp;&le;&nbsp;<i>f</i>&nbsp;&lt;&nbsp;0.5)</span><br>
					<span class="major">Major (<i>f</i>&nbsp;&ge;&nbsp;0.5)</span><br>
					<span class="unknown-frequency">Unknown</span></p>
					<!-- h3>Partial exome (20%)</h3>
					<p>Average coverage: 7x<br>
					Variants: 1000</p -->
					<!-- p><img src="http://chart.apis.google.com/chart?cht=bvs&chxt=x,y&chxl=0:|1||3||5||7||9||11||13||15||17||19||21||X|Y|1:|0%20Mb|2.5%20Mb&chs=264x80&chd=t:0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5,0.5|2.45,2.43,1.99,1.92,1.81,1.71,1.58,1.46,1.35,1.35,1.35,1.33,1.14,1.05,1.00,0.900,0.817,0.778,0.638,0.636,0.470,0.495,1.53,0.510&chco=4cb825,c4e8b7&chbh=2,6&chds=0,2.5" width="264" height="80" alt="[To be completed later]"></p -->
				</div>
			</div>
			<div id="results">
<?php
foreach (array('get-evidence' => 'GET/Evidence (beta)', 'omim' => 'OMIM', 'snpedia' => 'SNPedia', 'hgmd' => 'HGMD', 'pharmgkb' => 'PharmGKB (beta)', 'morbid' => 'Other hypotheses') as $k => $v):
	if (!isset($phenotypes[$k])) continue;
?>
			<h3 class="toggle"><?php echo htmlspecialchars($v); ?><?php if (array_key_exists($k, $phenotypes) && count($phenotypes[$k])): ?> <span class="count">(<?php echo count($phenotypes[$k]); ?>)</span><?php endif; ?></h3>
			<div class="data">
				<table class="sortable data" width="100%">
					<col width="25%">
					<col width="25%">
<?php if (array_key_exists($k, $phenotypes) && count($phenotypes[$k]) && array_key_exists('score', $phenotypes[$k][0])): ?>
					<col width="40%">
					<col width="10%">
<?php else: ?>
					<col width="50%">
<?php endif; ?>
					<thead>
						<tr>
							<th scope="col" class="keep"><div>Coordinates<br>
							<?php if (array_key_exists($k, $phenotypes) && count($phenotypes[$k]) && array_key_exists('gene', $phenotypes[$k][0]) && array_key_exists('amino_acid_change', $phenotypes[$k][0])): ?><i>Gene, amino acid change</i><?php else: ?><i>Function</i><?php endif; ?></div></th>
							<th scope="col" class="no-sort">Genotype<?php if (array_key_exists($k, $phenotypes) && count($phenotypes[$k]) && array_key_exists('trait_allele', $phenotypes[$k][0])): ?><br>
							<i>Trait-associated allele</i><?php endif; ?></th>
							<th scope="col" class="text"><div>Associated trait</div></th>
<?php if (array_key_exists($k, $phenotypes) && count($phenotypes[$k]) && array_key_exists('score', $phenotypes[$k][0])): ?>
							<th scope="col" class="sort-first-descending number"><div>Score</div></th>
<?php endif; ?>
						</tr>
					</thead>
					<tbody>
<?php
foreach ($phenotypes[$k] as $o):

// these variables are re-used; don't let previous values taint the output
unset($maf, $taf, $minor, $rare, $freq_unknown, $url);

// last-minute allele frequency calculations; for now, we give every
// variant the benefit of the doubt and use the lowest allele frequency
// for any population in which the subject claims to have ancestry
if (array_key_exists('maf', $o) && $o['maf'] != "N/A")
{
	$mafs = array_intersect_key(array_change_key_case(get_object_vars($o['maf']), CASE_LOWER),
	                            array_flip($phenotypes['ancestry']));
	if (count($mafs))
	{
		$freq_unknown = FALSE;
		$maf = min($mafs);
		$minor = $maf < 0.5;
		$rare = $maf < 0.05;
	}
	else
	{
		$freq_unknown = TRUE;
	}
}
else
{
	$freq_unknown = TRUE;
}

// trait allele frequencies are used over maf values, where available
if (array_key_exists('taf', $o) && $o['taf'] != "N/A")
{
	$tafs = array_intersect_key(array_change_key_case(get_object_vars($o['taf']), CASE_LOWER),
	                            array_flip($phenotypes['ancestry']));
	if (count($tafs))
	{
		$taf = min($tafs);
		$minor = $taf < 0.5;
		$rare = $taf < 0.05;
	}
}

// last-minute presentational corrections
// for this we need the chromosome name minus the "chr" prefix
$chromosome_without_prefix = str_replace('chr', '', $o['chromosome']);

// format genotypes: snpedia gives actual semicolon-separated genotypes;
// others give only a list of alleles--we treat these differently
if (strpos($o['genotype'], ';') !== FALSE)
{
	$o['genotype'] = str_replace(';', '/', $o['genotype']);
	if (!(is_numeric($chromosome_without_prefix) ||
	  ($chromosome_without_prefix == 'X' && $phenotypes['sex'] == 'female')))
	{
		$alleles = array_unique(explode('/', $o['genotype']));
		if (count($alleles) == 1)
			$o['genotype'] = $alleles[0];
	}
}
else if (is_numeric($chromosome_without_prefix) ||
  ($chromosome_without_prefix == 'X' && $phenotypes['sex'] == 'female'))
{
	if (strpos($o['genotype'], '/') === FALSE)
		$o['genotype'] = $o['genotype'].'/'.$o['genotype'];
}

$v = preg_split('/\t/', $o['variant']);

// format reference links
$references = explode(',', $o['reference']);
//TODO: do something about showing more than the first reference
//TODO: do something about LSDBs referenced in HGMD
$reference = explode(':', $references[0]);
switch ($reference[0])
{
case 'dbsnp':
	$article_id = $reference[1];
	$url = "http://www.snpedia.com/index.php/{$article_id}";
	break;
case 'omim':
	$allele_id = explode('.', $reference[1]);
	$article_id = $allele_id[0];
	$url = "http://www.ncbi.nlm.nih.gov/entrez/dispomim.cgi?id={$article_id}";
	break;
case 'pmid':
	$pmid = $reference[1];
	$url = "http://www.ncbi.nlm.nih.gov/pubmed/{$pmid}";
	break;
case 'gwas':
	$rsid = $reference[1];
	$url = "http://www.genome.gov/gwastudies/?snp={$rsid}&submit=Search#result_table";
	break;
case 'http':
	$url = $references[0];
	break;
}
?>
						<tr class="<?php if ($freq_unknown): ?>unknown-frequency<?php elseif ($rare): ?>rare<?php elseif ($minor): ?>minor<?php else: ?>major<?php endif; ?>">
							<td><?php echo $o['chromosome'].':'.$o['coordinates']; ?><br>
							<?php if (array_key_exists('gene', $o) && !empty($o['gene']) && array_key_exists('amino_acid_change', $o)): ?><i><?php echo $o['gene']; ?>, <?php echo $o['amino_acid_change']; ?></i><?php else: ?><i><span class="dim">(Not computed)</span></i><?php endif; ?></td>
							<td><?php echo $o['genotype']; ?><?php if (array_key_exists('trait_allele', $o)): ?><br>
							<i><?php echo $o['trait_allele']; ?></i><?php endif; ?></td>
							<td><?php if(isset($url)): ?><a href="<?php echo $url; ?>"><?php endif; echo $o['phenotype']; if (isset($url)): ?></a><?php endif; ?></td>
<?php if (array_key_exists('score', $o)): ?>
							<td scope="col" class="number"><?php echo $o['score']; ?></td>
<?php endif; ?>
						</tr>
<?php endforeach; ?>
<?php if (!array_key_exists($k, $phenotypes) || !count($phenotypes[$k])): ?>
						<tr>
							<td colspan="3"><span><br>No results available<br><br></span></td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
			</div>
<?php
endforeach;
?>
			</div>
		</div>
	</div></div>
<!-- progress: <?= $progress_json ?> -->
	<div id="foot"><div>
		<div id="copyright">
			<p>
				<span>Copyright &copy; MMIX President and Fellows of Harvard College<?php if(!isset($suppress_timing_data)): ?><br>[{elapsed_time} s]<?php endif; ?></span>
			</p>
		</div>
	</div></div>
</body>
</html>