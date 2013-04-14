<?php

	$sites['Content syndication']['Posterous']['fields']=array(
					'email'=>'',			// Email				[str]
					'password'=>'',			// Password				[str]
					'url'=>'',				// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Content syndication']['Posterous']['spin']=array('title','content');
	$sites['Content syndication']['Posterous']['site']='posterous.com';
	$sites['Content syndication']['Posterous']['signup']='https://posterous.com/';


	function t09_sb_posterous($id,$acc,$sb,$fuzz=false){
		$post=get_post($id);
		$perma=get_permalink($id);
		/*
		$title=urlencode($post->post_title);
		$content=urlencode(t09_sb_sh(substr($post->post_content,0,150)).'... <a href="'.$perma.'">Read more</a>');
		*/
		$u=$acc['email'];
		$p=$acc['password'];
		$purl=$sb->profile_url($acc);

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$content=$fuzz['text'];
			$content.="&nbsp;<a href='$perma'>Read story</a>";
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$content=substr(apply_filters('the_content',$post->post_content),0,150).'...';
			$content.="&nbsp;<a href='$perma'>Read story</a>";
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					$tpl=$sb->format($acc['type'],null,array('title'=>$title,'content'=>$content,'perma'=>$perma),$id,$fuzz);
					$title=$tpl['title'];
					$content=$tpl['content'];
					$perma=$tpl['perma'];
				break;
			}
		}
		

		$title=$sb->preprocess('title',$title,$acc);
		$content=$sb->preprocess('content',$content,$acc);

		$ch=curl_init();
		$cookie = tempnam("/tmp", "pst");
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		#$ck=tempnam();
        $url = 'https://posterous.com/login';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE,$cookie);
		curl_setopt($ch, CURLOPT_COOKIEJAR,$cookie);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_REFERER,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $sb->ua());
        $b0=curl_exec($ch);

		$form=$sb->find_form($b0,'authenticity_token');
		$form=$sb->set_formfield($form,'user[mail]',$u);
		$form=$sb->set_formfield($form,'user[password]',$p);

		#print_r($form);

        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$sb->form2args($form));
        $b2=curl_exec($ch);
		#echo $b2;

		$newpost='http://posterous.com/posts/new';
        curl_setopt($ch, CURLOPT_URL, $newpost);
        $b3=curl_exec($ch);
		#echo $b3;

		$form=$sb->find_form($b3,'post[draft]');
		if(!$form){
			$sb->sblog($purl['url'],"Something went wrong (no form found)",1);
			return;
		}
		$form=$sb->set_formfield($form,'post[title]',$title);
		$form=$sb->set_formfield($form,'post[body]',$content);

		$posturl='http://posterous.com/posts/create';
        curl_setopt($ch, CURLOPT_URL, $posturl);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$sb->form2args($form));
        $b4=curl_exec($ch);
		
		// Done
		return;

	}
	function t09_sb_posterous_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/posterous.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="'.$acc['url'].'">'.str_replace('http://','',$acc['url']).'</a></li>';
	}
?>