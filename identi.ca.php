<?php
	$sites['Microblogs']['identi.ca']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Microblogs']['identi.ca']['spin']=array('status','hashtags');
	$sites['Microblogs']['identi.ca']['site']='identi.ca';
	$sites['Microblogs']['identi.ca']['signup']='https://identi.ca/main/register';

	function t09_sb_identica($id,$acc,$sb,$fuzz=false){
		$u=$acc['user'];
		$p=$acc['password'];

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
			$status="$title $perma";
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$status="$title $perma";
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					//$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					//$hash=$sb->format($acc['type'],'hashtags','',$id,$fuzz);
					$tpl=$sb->format($acc['type'],null,array('status'=>$status,'hashtags'=>'','perma'=>$perma),$id,$fuzz);
					$status=$tpl['status'];
					$hash=$tpl['hashtags'];
					$perma=$tpl['perma'];
				break;
			}
		}
		$status=$sb->preprocess('status',$status,$acc);

		$url='https://identi.ca/main/login';
		$args='nickname='.urlencode($u).'&password='.urlencode($p).'&submit=Login';

		$cookie = tempnam("tmp", "identica");

		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$args);


		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.20 (.NET CLR 3.5.30729)');

		$b1=curl_exec($ch);

		$url='http://identi.ca/';
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$b2=curl_exec($ch);


		$str='@name="token" type="hidden" id="token" value="([^"]+)"@si';
		preg_match ($str, $b2, $matches);
		$token = urlencode($matches[1]);


		if($token){
			// post
			$status=urlencode($status);
			$args="token=$token&status_textarea=$status&MAX_FILE_SIZE=5000000&returnto=public&inreplyto=&lat=&lon=&location_id=&location_ns=&notice_data-geo=1&status_submit=1";
			$url='http://identi.ca/notice/new';

			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$args);
			$b3=curl_exec($ch);

			#echo $b3;

		}else{
			# echo $b1;
			# error checking:
			# banned
			# cannot post notices
		}

	}
	function t09_sb_identica_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/identi.ca.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://identi.ca/'.$acc['user'].'">identi.ca/'.$acc['user'].'</a></li>';
	}
?>