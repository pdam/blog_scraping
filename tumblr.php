<?php
	$sites['Microblogs']['tumblr']['fields']=array(
					'email'=>'',			// Username				[str]
					'password'=>'',			// Password				[str]
					'blog'=>'',				// Blog					[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Microblogs']['tumblr']['spin']=array('title','content');
	$sites['Microblogs']['tumblr']['site']='tumblr.com';
	$sites['Microblogs']['tumblr']['signup']='https://www.tumblr.com/register';

	function t09_sb_tumblr($id,$acc,$sb,$fuzz=false){
		$u=$acc['email'];
		$p=$acc['password'];
		
		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$content=$fuzz['text'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			#$content.="&nbsp;<a href='$perma'>Read story</a>";
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$content=substr(t09_sb_sh(apply_filters('the_content',$post->post_content)),0,150).'...';
			#$content.="&nbsp;<a href='$perma'>Read story</a>";
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

		#$title=urlencode($title);
		#$perma=urlencode($perma);
		#$content=urlencode($content);

		$ch = curl_init();
		$ck=tempnam('','tumblr');
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		
		curl_setopt($ch,CURLOPT_URL,'https://www.tumblr.com/login');
		curl_setopt($ch,CURLOPT_COOKIESESSION,true);
		curl_setopt($ch,CURLOPT_COOKIEJAR,$ck);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$ck);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.1; WOW64; U; Edition United Kingdom Local; en) Presto/2.10.289 Version/12.02');
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 0);
		$b0=curl_exec($ch);	

		#echo $b0;

		$form=$sb->find_form($b0,'user[email]');

		if(!$form){
			// SBLOG
			return;
		}


		$form=$sb->set_formfield($form,'user[email]',$u);
		$form=$sb->set_formfield($form,'user[password]',$p);

		curl_setopt($ch,CURLOPT_HEADER,true);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$sb->form2args($form));
		$b1=curl_exec($ch);

		#echo htmlentities($b1);

		$cookie=preg_match_all('/Set-Cookie: ([^;]+);/',$b1,$ck);
		if(count($ck[1])){
			for($i=0;$i<count($ck[1]);$i++){
				curl_setopt($ch,CURLOPT_COOKIE,$ck[1][$i]);
			}
		}

		curl_setopt($ch,CURLOPT_HEADER,true);
		curl_setopt($ch,CURLOPT_POST,false);
		curl_setopt($ch,CURLOPT_URL,'http://www.tumblr.com/dashboard');
		$b2=curl_exec($ch);
		#echo $b2;
		#return;

		curl_setopt($ch,CURLOPT_HEADER,true);
		curl_setopt($ch,CURLOPT_URL,'http://www.tumblr.com/blog/'.strtolower($acc['blog']).'/new/text');
		#echo 'http://www.tumblr.com/blog/'.strtolower($acc['blog']).'/new/text';
		$b3=curl_exec($ch);
		#echo $b3;
		$form=$sb->find_form($b3,'post[one]');

		if($form){
			// Creates
			$form['inputs'][]=array('name'=>'post[promotion_data][message]','value'=>'(No message)');
			$form['inputs'][]=array('name'=>'post[promotion_data][icon]','value'=>'/images/highlighted_posts/icons/bolt_white.png');
			$form['inputs'][]=array('name'=>'post[promotion_data][color]','value'=>'#bb3434');

			// Sets
			$form=$sb->set_formfield($form,'post[source_url]',($perma));
			$form=$sb->set_formfield($form,'post[one]',$title);
			$form=$sb->set_formfield($form,'post[two]','<p>'.$content.'</p>');
			$form=$sb->set_formfield($form,'is_rich_text[two]','1');

			// Unsets
			/*
			$form=$sb->unset_formfield($form,'post[promotion_type]');
			$form=$sb->unset_formfield($form,'post[promotion_type]');
			$form=$sb->unset_formfield($form,'allow_photo_replies]');
			$form=$sb->unset_formfield($form,'allow_answers');
			$form=$sb->unset_formfield($form,'send_to_fbog');
			$form=$sb->unset_formfield($form,'send_to_twitter');
			$form=$sb->unset_formfield($form,'post[typekit]');
			*/

		}else{
			return;
		}
		
		$postargs=$sb->form2args($form);
		unset($postargs['post[promotion_type]']);
		unset($postargs['allow_photo_replies']);
		unset($postargs['allow_answers']);
		unset($postargs['send_to_fbog']);
		unset($postargs['send_to_twitter']);
		unset($postargs['post[typekit]']);

		// fix this bug
		$postargs['post[state]']='0';

		#print_r($postargs);

		/*
			secure_form_key	!331347724748|iX4XR76H2pJqTD5CrE4lhfXmkA
			post[state]	0
			post[publish_on]	
			post[draft_status]	
			post[date]	now
			post[source_url]	http://www.google.cn
			post[tags]	
			post[slug]	
			custom_tweet	[URL]
			custom_tweet_changed	0
			is_rich_text[one]	0
			is_rich_text[two]	1
			is_rich_text[three]	0
			form_key	H9uz28x408eO8bzjklEV7qZJWps
			post[one]	test post
			post[two]	<p>This is my post</p>
			post[type]	regular
			post[promotion_data][message]	(No message)
			post[promotion_data][icon]	/images/highlighted_posts/icons/bolt_white.png
			post[promotion_data][color]	#bb3434
		*/

		#curl_setopt($ch,CURLOPT_URL,'http://www.tumblr.com'.$form['attr']['action']);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postargs);
		$buffer=curl_exec($ch);
		#echo $buffer;
	}
	function t09_sb_tumblr_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/tumblr.16x16.png';
		echo '<li><img src="'.$img.'" align="middle"> &nbsp;<a href="http://'.$acc['blog'].'.tumblr.com">'.$acc['blog'].'.tumblr.com</a></li>';
	}
?>