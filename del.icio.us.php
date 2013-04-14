<?php
	$sites['Social bookmarking']['del.icio.us (non-Yahoo accounts)']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social bookmarking']['del.icio.us (non-Yahoo accounts)']['spin']=array('title','tags');
	$sites['Social bookmarking']['del.icio.us (non-Yahoo accounts)']['site']='delicious.com';
	$sites['Social bookmarking']['del.icio.us (non-Yahoo accounts)']['signup']='https://delicious.com/register';

	function t09_sb_deliciousnonyahooaccounts($id,$acc,$sb,$fuzz=false){

		$u=$acc['user'];
		$p=$acc['password'];


		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			$tags=$fuzz['title'];
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$tags=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
		}
		if(method_exists($sb,'format')){
			switch(true){
				case ($acc['spin']):
				case ($fuzz&&$sb->opts['spinfuzz']):
					//$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					$tpl=$sb->format($acc['type'],null,array('title'=>$title,'perma'=>$perma,'tags'=>$tags),$id,$fuzz);
					$title=$tpl['title'];
					$tags=$tpl['tags'];
					$perma=$tpl['perma'];
				break;
			}
		}

		$title=$sb->preprocess('title',$title,$acc);
		$tags=$sb->preprocess('tag',$tags,$acc);

		$perma=urlencode($perma);
		$title=urlencode($title);
		$tags=urlencode($tags);

		/*
		// Login
		$url='https://www.delicious.com/login';

		$ch = curl_init();
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$cookie = tempnam("/tmp", "tw");

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

		
		if(!$bi){
			// No data returned
			$sb->sblog($purl['url'],"No data returned by delicious. Check your proxy settings.",1);
			return;
		}
		$form=$sb->find_form($bi,'username');
		$form=$sb->set_formfield($form,'username',$u);
		$form=$sb->set_formfield($form,'password',$p);

		print_r($form);
		return;
		
		// Login
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sb->form2args($form));
		$b1=curl_exec($ch);

		echo $b1;
	
		$bookmark='http://www.delicious.com/save?url='.urlencode($perma).'&title='.urlencode($title).'&notes=&v=6&noui=1&jump=doclose';
		echo $bookmark;
		*/

		// DEPRECATED V1 API
		$api = "api.del.icio.us/v1";
		$get = "https://$u:$p@$api/posts/add?&url=$perma&description=$title&extended=&tags=$tags";
		$ch = curl_init();

		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		curl_setopt($ch, CURLOPT_URL,$get);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		#curl_setopt($ch, CURLOPT_USERPWD, "$u:$p");
        curl_setopt($ch, CURLOPT_USERAGENT, $sb->ua());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER,1);
		$xml = curl_exec ($ch);
		#print_r($xml);
		if(strpos($xml,'"done"')===false)$sb->sblog('','Failed to post to delicious.com/'.$u,1);

		curl_close ($ch);
		#echo $get;
		#echo htmlentities($xml);
	}
	function t09_sb_deliciousnonyahooaccounts_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/del.icio.us.16x16.png';
		echo '<li><img src="'.$img.'" align="middle"> &nbsp;<a href="http://delicious.com/'.$acc['user'].'">delicious.com/'.$acc['user'].'</a></li>';
	}
?>