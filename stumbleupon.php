<?php
	$sites['Social Networks']['Stumbleupon']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'interest'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social Networks']['Stumbleupon']['spin']=array('title','content','tags');
	$sites['Social Networks']['Stumbleupon']['site']='stumbleupon.com';
	$sites['Social Networks']['Stumbleupon']['signup']='http://www.stumbleupon.com/signup.php?pre=homepage';

	function t09_sb_stumbleupon($id,$acc,$sb,$fuzz=false){

		$u=$acc['user'];
		$p=$acc['password'];

		// Log in
		$u=urlencode($u);
		$p=urlencode($p);

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$tags=$fuzz['title'];
			$content=$fuzz['text'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$tags=str_replace(' ',',',$title);
			$perma=get_permalink($id);
			$content=substr(apply_filters('the_content',$post->post_content),0,150).'...';
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			//"$title $perma";
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					//$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					//$content=$sb->format($acc['type'],'content',$content,$id,$fuzz);
					//$tags=$sb->format($acc['type'],'tags',$tags,$id,$fuzz);
					
					$tpl=$sb->format($acc['type'],null,array('title'=>$title,'content'=>$content,'tags'=>$tags,'perma'=>$perma),$id,$fuzz);

					$title=$tpl['title'];
					$content=$tpl['content'];
					$tags=$tpl['tags'];
					$perma=$tpl['perma'];
				break;
			}
		}
		$title=$sb->preprocess('title',$title,$acc);
		$content=$sb->preprocess('content',$content,$acc);
		$tags=$sb->preprocess('tag',$tags,$acc);
		$interest=$acc['interest'];

		$utitle=urlencode($title);
		$utopic=urlencode($tags);
		$ureview=urlencode($content);
		$uperma=urlencode($perma);

		$cookie = tempnam("/tmp", "mlv");
		
		$url = "http://www.stumbleupon.com/";
		$args="username=$u&password=$p&login=Login";

		$ch = curl_init();

		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		// Step 1
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)');
		$b0 = curl_exec($ch);

		// Step 2
		$url='https://www.stumbleupon.com/login';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$b0b = curl_exec($ch);

		// Grab token
		# id="token" value="c76d8aa57e71795ec1c02387a1eb1abd"
		
		$str='@id="token" value="([^"]+)"@si';
		preg_match($str,$b0b,$m);
		$token=$m[1];
	
		$url='https://www.stumbleupon.com/login';
		$args='_output=Json&user='.$u.'&pass='.$p.'&remember=true&_action=auth&_token='.$token.'&_method=create';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$b1=curl_exec($ch);


		$url='http://www.stumbleupon.com/submit?title='.$utitle.'&url='.$uperma;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		$b2 = curl_exec($ch);

		$str="@({[^;]+badge[^;]+});@";
		preg_match($str,$b2,$matches);
		$badge = json_decode($matches[1]);

		if(!$badge->token&&!$badge->likeUrl){
			$str='@"([a-z_]token)" value="([^"]+)"@';
			preg_match ($str, $b2, $matches);
			$tokenF = $matches[1];
			$tokenV = $matches[2];
			#$args='language=EN&nsfw=false&tags=&user-tags='.$utopic.'&review='.$ureview.'&language=EN&'.$tokenF.'='.$tokenV.'&action=_submitUrl';
			$args="_output=Json&url=$uperma&nsfw=false&tags=$interest&user-tags=$utopic&review=$ureview&language=EN&$tokenF=$tokenV&_action=submitUrl&_method=create";

			#curl_setopt($ch, CURLOPT_URL, 'http://www.stumbleupon.com/submit');//?url='.$uperma);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			$b2=curl_exec($ch);

			$str="@({[^;]+badge[^;]+});@";
			preg_match ($str, $b2, $matches);
			$badge = json_decode($matches[1]);
			
		}
		/*
		// exists in DB
		curl_setopt($ch, CURLOPT_URL, 'http://www.stumbleupon.com'.$badge->likeUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'token='.$badge->token);
		$b3=curl_exec($ch);
		*/

		return;

	}
	function t09_sb_stumbleupon_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/stumbleupon.16x16.png';
		echo '<li><img src="'.$img.'" align="middle"> &nbsp;<a href="http://www.stumbleupon.com/stumbler/'.$acc['user'].'/">stumbleupon.com/'.$acc['user'].'</a></li>';
	}
?>