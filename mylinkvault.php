<?php

	$sites['Social bookmarking']['MyLinkVault']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social bookmarking']['MyLinkVault']['spin']=array('title','content');
	$sites['Social bookmarking']['MyLinkVault']['site']='mylinkvault.com';
	$sites['Social bookmarking']['MyLinkVault']['signup']='http://www.mylinkvault.com/users/register.php';

	function t09_sb_mylinkvault($id,$acc,$sb,$fuzz=false){

		// Post details
		$u=$acc['user'];
		$p=$acc['password'];
		/*
		$post=get_post($id);
		$perma=get_permalink($id);
		$title=($post->post_title);
		$content=substr(t09_sb_sh($post->post_content),0,150);
		*/

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$content=$fuzz['text'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$content=substr(t09_sb_sh(apply_filters('the_content',$post->post_content)),0,150);
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					$content=$sb->format($acc['type'],'content',$content,$id,$fuzz);
				break;
			}
		}
		$title=$sb->preprocess('title',$title,$acc);
		$content=$sb->preprocess('content',$content,$acc);

		// Log in
		$u=urlencode($u);
		$p=urlencode($p);

		$cookie = tempnam("/tmp", "mlv");
		$url = "http://www.mylinkvault.com/users/";
		$args="login_username=$u&login_password=$p&remember=";

		$ch = curl_init();
		
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b0 = curl_exec($ch);

		// Get new link URL
		$str='@class="newLink" href="([^"]+)"@si';
		preg_match($str,$b0,$m);

		$url='http://www.mylinkvault.com'.$m[1];
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$b1 = curl_exec($ch);

		/*
		// Get POST URL
		$str='@form action="([^"]+)" method="post"@si';
		preg_match($str,$b1,$m);
		$url='http://www.mylinkvault.com'.$m[1];
		*/

		$url='http://www.mylinkvault.com/lib/server.php/apiws/linkop/';

		// Get 'other' category
		$str='@value="([^"]+)" selected="selected">Other@si';
		preg_match($str,$b1,$m);
		$other=$m[1];

		$args='<?xml version="1.0" encoding="UTF-8"?><r><a><e k="0"><i v="0"/></e><e k="1"><a><e k="p7"><a><e k="0"><s>'.$title.'</s></e><e k="1"><s>'.$perma.'</s></e><e k="2"><s>'.$content.'</s></e><e k="3"><s>'.$other.'</s></e><e k="4"><s>1</s></e></a></e></a></e></a></r>';

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: text/xml; charset=UTF-8','Content-Length: '.strlen($args)));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b2 = curl_exec($ch);
	}
	function t09_sb_mylinkvault_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/mylinkvault.com.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://www.mylinkvault.com/'.$acc['user'].'/page-1.htm">mylinkvault.com/'.$acc['user'].'</a></li>';
	}

?>