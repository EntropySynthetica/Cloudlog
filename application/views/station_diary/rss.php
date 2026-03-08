<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?php echo htmlspecialchars('Station Diary - ' . $callsign, ENT_QUOTES); ?></title>
		<link><?php echo site_url('station-diary/' . rawurlencode($callsign)); ?></link>
		<description><?php echo htmlspecialchars('Public station diary feed for ' . $callsign, ENT_QUOTES); ?></description>
		<language>en-us</language>
		<atom:link href="<?php echo htmlspecialchars($current_url, ENT_QUOTES); ?>" rel="self" type="application/rss+xml" />
		<?php foreach ($entries as $entry) { ?>
			<item>
				<title><?php echo htmlspecialchars($entry->title, ENT_QUOTES); ?></title>
				<link><?php echo site_url('station-diary/' . rawurlencode($callsign)); ?></link>
				<guid><?php echo site_url('station-diary/' . rawurlencode($callsign)) . '#entry-' . (int)$entry->id; ?></guid>
				<pubDate><?php echo date(DATE_RSS, strtotime($entry->created_at)); ?></pubDate>
				<description><![CDATA[<?php echo $entry->note; ?>]]></description>
			</item>
		<?php } ?>
	</channel>
</rss>
