<?php
	$sites['Microblogs']['twitter']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Microblogs']['twitter']['spin']=array('status','hashtags');
	$sites['Microblogs']['twitter']['site']='twitter.com';
	$sites['Microblogs']['twitter']['signup']='http://twitter.com/';

	function t09_sb_twitter($id,$acc,$sb,$fuzz=false){
		$u=$acc['user'];
		$p=$acc['password'];

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$content=$fuzz['text'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$status="$title $perma";
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
					//$status=$sb->format($acc['type'],'status',$title,$id,$fuzz);
					//$hash=$sb->format($acc['type'],'hashtags','',$id,$fuzz);
					$tpl=$sb->format($acc['type'],null,array('status'=>$status,'hash'=>$hash,'perma'=>$perma),$id,$fuzz);
					$status=$tpl['status'];
					$hash=$tpl['hash'];
					$perma=$tpl['perma'];
				break;
			}
		}
		$status=$sb->preprocess('status',$status,$acc);
		$status.=$hash;

		$ch=curl_init();
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$cookie = tempnam("/tmp", "tw");

		$url = 'https://mobile.twitter.com/session/new';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIESESSION,true);
		curl_setopt($ch, CURLOPT_COOKIEFILE,$cookie);
		curl_setopt($ch, CURLOPT_COOKIEJAR,$cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, $sb->ua());

		$bi=curl_exec($ch);

		$purl=$sb->profile_url($acc);

		if(!$bi){
			// No data returned
			$sb->sblog($purl['url'],"No data returned by twitter. Check your proxy settings.",1);
			return;
		}

		$form=$sb->find_form($bi,'username');
		if(!$form){
			// No login form found
			if(strtr(strtolower($bi),'exceeded')){
				$sb->sblog($purl['url'],"Twitter access forbidden. Server IP or proxies may be banned.",1);
			}else{
				$sb->sblog($purl['url'],"No twitter login forms found. Might be down. Cannot post.",1);
			}
			return;
		}
		$form=$sb->set_formfield($form,'username',$u);
		$form=$sb->set_formfield($form,'password',$p);

		// Login
		curl_setopt($ch, CURLOPT_URL, $form['attr']['action']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sb->form2args($form));
		$b1=curl_exec($ch);

		// Redirected
		$url='https://mobile.twitter.com/compose/tweet';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, false);
		$b2=curl_exec($ch);


		$form=$sb->find_form($b2,'tweet[text]');

		if(!$form){
			// No status update form found
			$sb->sblog($purl['url'],"No twitter status box found. Check credentials. Cannot post.",1);
			return;
		}

		// Post
		$form=$sb->set_formfield($form,'tweet[text]',$status);
		$form['attr']['action']='https://mobile.twitter.com/';
		curl_setopt($ch, CURLOPT_URL, $form['attr']['action']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sb->form2args($form));
		$b2=curl_exec($ch);
		return;
	}
	function t09_sb_twitter_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/twitter.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://twitter.com/'.$acc['user'].'">twitter.com/'.$acc['user'].'</a></li>';
	}
?>