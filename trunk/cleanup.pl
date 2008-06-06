#!/usr/bin/perl

use strict;
use File::Copy;

my ($btpd_dir, $download_dir, $outgoing_dir, $links_dir);
$btpd_dir = '/pub/p2p/.btpd/';
$download_dir = '/pub/p2p/downloads/download/';
$outgoing_dir = '/pub/p2p/downloads/outgoing/';
$links_dir = '/pub/p2p/downloads/';

my ($filename, %active_hashesh);
opendir(DIR, $btpd_dir . '/torrents') or die("Error in opening dir $btpd_dir/torrents : $!\n");
while( ($filename = readdir(DIR))) {
    next if ($filename !~ /^[a-f0-9]{40}$/);
    next if (! -d $btpd_dir . '/torrents/' . $filename);
    $active_hashesh{$filename} = 1;
}
closedir(DIR);

opendir(DIR, $download_dir) or die("Error in opening dir $download_dir : $!\n");
while( ($filename = readdir(DIR))) {
    next if ($filename !~ /^[a-f0-9]{40}$/);
    next if (! -d $download_dir . $filename);
    if (! $active_hashesh{$filename}) {
	move($download_dir . $filename, $outgoing_dir . $filename) or warn('error moving ' . $download_dir . $filename . ' to ' . $outgoing_dir . $filename . " : $!\n");
    }
}
closedir(DIR);

opendir(DIR, $links_dir) or die("Error in opening dir $links_dir : $!\n");
while( ($filename = readdir(DIR))) {
    my ($link,$base);
    next if (! -l  $links_dir . $filename);
    next if (-e $links_dir . $filename);
    unlink($links_dir . $filename) or warn("unable unlink $links_dir$filename : $!\n");
}
closedir(DIR);
