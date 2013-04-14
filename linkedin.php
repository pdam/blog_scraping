<?php

	$sites['Social Networks']['Linkedin']['fields']=array(
					'email'=>'',			// Email				[str]
					'password'=>'',			// Password				[str]
					'full_name'=>'',		// First, Last			[str]
					'profile_url'=>'',		// URL					[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social Networks']['Linkedin']['spin']=array('status');
	$sites['Social Networks']['Linkedin']['site']='linkedin.com';
	$sites['Social Networks']['Linkedin']['signup']='http://www.linkedin.com/';

	function t09_sb_linkedin($id,$acc,$sb,$fuzz=false){

		// Log in (mobile)
		$u=urlencode($u);
		$p=urlencode($p);

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$content=$fuzz['text'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$status="$title $url";
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
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
					$status=$sb->format($acc['type'],'status',$status,$id,$fuzz);
				break;
			}
		}
		$status=$sb->preprocess('status',$status,$acc);

		$ch=curl_init();
		$cookie = tempnam("tmp", "linkedin");
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$url="https://www.linkedin.com/secure/login";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.20 (.NET CLR 3.5.30729)');
		$b0=curl_exec($ch);

		$str='@name="csrfToken" value="([^"]+)"@si';
		preg_match ($str, $b0, $matches);
		$csrf = urlencode($matches[1]);

		$args="csrfToken=$csrf&source_app=&session_key=$u&session_password=$p&session_login=Sign+In&session_login=&session_rikey=";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b1=curl_exec($ch);

		// Go to home
		$url='http://www.linkedin.com/home';
		curl_setopt($ch, CURLOPT_URL, $url);
		$b2=curl_exec($ch);

		$str='@name="csrfToken" value="([^"]+)"@si';
		preg_match ($str, $b2, $matches);
		$csrf = urlencode($matches[1]);

		$str='@name="sourceAlias" value="([^"]+)"@si';
		preg_match ($str, $b2, $matches);
		$alias = urlencode($matches[1]);

		// Post update
		$post=urlencode($status);
		$url='http://www.linkedin.com/share?submitPost=';

		$args="ajax=true&contentImageCount=0&contentImageIndex=-1&contentImage=&contentEntityID=&contentUrl=&postText=$post&contentTitle=&contentSummary=&contentImageIncluded=true&%23=&postVisibility=EVERYONE&submitPost=&tetherAccountID=&csrfToken=$csrf&sourceAlias=$alias";

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b3=curl_exec($ch);
	}
	function t09_sb_linkedin_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/linkedin.com.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="'.$acc['profile_url'].'">'.$acc['full_name'].'</a></li>';
	}
?>