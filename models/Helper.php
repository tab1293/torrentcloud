<?php

	class Helper {

		public function formatBytes($size, $precision = 2) {
			if($size <= 0)
				return 0 . ' B';
				
			$base = log($size) / log(1024);
			$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   

			return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
		}

		function convertSecToStr($secs) {
			$output = '';
			if($secs < 0) {
				return "Unknown time";
			}
			if($secs >= 86400) {
				$days = floor($secs/86400);
				$secs = $secs%86400;
				$output = $days.' day';
				if($days != 1) $output .= 's';
				if($secs > 0) $output .= ', ';
			}
			if($secs>=3600){
				$hours = floor($secs/3600);
				$secs = $secs%3600;
				$output .= $hours.' hour';
				if($hours != 1) $output .= 's';
				if($secs > 0) $output .= ', ';
			}
			if($secs>=60){
				$minutes = floor($secs/60);
				$secs = $secs%60;
				$output .= $minutes.' minute';
				if($minutes != 1) $output .= 's';
				if($secs > 0) $output .= ', ';
			}
			$output .= $secs.' second';
			if($secs != 1) $output .= 's';
			return $output;
		}

		function parseTorrentUrl($url) {
			$parsedUrl = parse_url($url);
			if($parsedUrl['scheme'] == "magnet") {
				$magnetURI = new MagnetUri($url);
				$xt = $magnetURI->xt;
				$splitLink = split(":", $xt);
				if ($splitLink[1] == "btih") {
					$magnetHash = $splitLink[2];
					return array("hash"=>$magnetHash, "url"=>$url);
				} else {
					$response = array("error"=>"Not a valid magnet link");
					echo json_encode($response);
					return;
				}
			} else if($parsedUrl['scheme'] == "http") {

			} else {

			}
		}

	}

?>