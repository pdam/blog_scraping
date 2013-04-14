<?php
	$sites['Social bookmarking']['Newsvine Seed']['fields']=array(
					'email'=>'',			// Username				[str]
					'password'=>'',			// Password				[str]
					'url'=>'',				// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social bookmarking']['Newsvine Seed']['spin']=array('title','content','tags');
	$sites['Social bookmarking']['Newsvine Seed']['site']='newsvine.com';
	$sites['Social bookmarking']['Newsvine Seed']['signup']='https://www.newsvine.com/_nv/accounts/register';
	function t09_sb_newsvineseed($id,$acc,$sb,$fuzz=false){
		// Post details
		$u=$acc['email'];
		$p=$acc['password'];

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
			$content=substr(t09_sb_sh(apply_filters('the_content',$post->post_content)),0,150).'...';
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					//$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					//$content=$sb->format($acc['type'],'content',$content,$id,$fuzz);
					$tpl=$sb->format($acc['type'],null,array('title'=>$title,'content'=>$content,'perma'=>$perma),$id,$fuzz);
					$title=$tpl['title'];
					$content=$tpl['content'];
					$perma=$tpl['perma'];
				break;
			}
		}
		$title=$sb->preprocess('title',$title,$acc);
		$content=$sb->preprocess('content',$content,$acc);

		// Log in
		$u=urlencode($u);
		$p=urlencode($p);
		$r=urlencode('https://www.newsvine.com/_nv/accounts/login');

		$cookie = tempnam("/tmp", "nv");
		$url = "https://www.newsvine.com/_nv/api/accounts/login";
		$args="redirect=$r&responseType=redirect&m=login&affiliate=newsvine.com&email=$u&password=$p";

		$ch = curl_init();

		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		$b0 = curl_exec($ch);

		// Get vars
		$url='http://www.newsvine.com/_tools/seed';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$b1 = curl_exec($ch);

		$str='@name="statusCode" value="([^"]+)"@si';
		preg_match($str,$b1,$m);
		$status=urlencode($m[1]);
		
		$str='@name="originId" value="([^"]+)"@si';
		preg_match($str,$b1,$m);
		$origin=urlencode($m[1]);
		
		$str='@name="submissionId" value="([^"]+)"@si';
		preg_match($str,$b1,$m);
		$subid=urlencode($m[1]);

		// Post
		$title=urlencode($title);
		$perma=urlencode($perma);
		$content=urlencode($content);
		$blogname=urlencode(get_bloginfo());

		$url = "http://www.newsvine.com/_action/tools/saveLink";

		$args="statusCode=$status&originId=$origin&submissionId=$subid&redirect=0&url=$perma&headline=$title&newsType=x&imgUrl=&imgHeight=&imgWidth=&popoff=0&categoryTag=-&blurb=$content&tags=$title&saveCityInput=&sourceName=&publishTo=groups&groupId[]=0";

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b1 = curl_exec($ch);
	}
	function t09_sb_newsvineseed_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/newsvine.16x16.png';
		echo '<li><img src="'.$img.'" align="middle"> &nbsp;<a href="'.$acc['url'].'/">'.$acc['url'].'</a></li>';
	}
?>