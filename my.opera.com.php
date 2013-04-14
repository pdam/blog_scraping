<?php
	$sites['Blogs']['my.opera.com']['fields']=array(
					'mms_email'=>'',		// MMS email address	[str]
					'username'=>'',			// Full blog address	[str]
					'password'=>'',			// Full blog address	[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Blogs']['my.opera.com']['spin']=array('title','content');
	$sites['Blogs']['my.opera.com']['site']='my.opera.com';
	$sites['Blogs']['my.opera.com']['signup']='http://www.linkedin.com/';

	function t09_sb_myoperacom($id,$acc,$sb,$fuzz=false){
		ob_start();
		$m=$acc['mms_email'];
		$b=$acc['blog'];
		$p=$acc['password'];
		$u=$acc['username'];
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
					$title=$sb->format($acc['type'],'title',$title,$id,$fuzz);
					$content=$sb->format($acc['type'],'content',$content,$id,$fuzz);
				break;
			}
		}
		$title=$sb->preprocess('title',$title,$acc);
		$content=$sb->preprocess('content',$content,$acc);


		$ch = curl_init();
		$cookie = tempnam("tmp", "myopera");
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$url = "https://my.opera.com/community/login/index.pl";
		$args="location=http%3A%2F%2Fmy.opera.com%2Fcommunity%2F&prevlocation=&user=$u&passwd=$p&remember=1";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
		curl_setopt($ch, CURLOPT_USERAGENT, $sb->ua());
		$b1 = curl_exec($ch);

		
		#curl_setopt($ch, CURLOPT_URL,'http://my.opera.com/'.$u.'/blog/addpost.dml');
		curl_setopt($ch, CURLOPT_URL,'http://my.opera.com/'.$u.'/blog/');
		$b2 = curl_exec($ch);

		// Scrape MMS email
		
		$str='/mailto:([^@]+@my.opera.com)/';
		preg_match($str,$b2,$m);
		if($m[1]){
			$mms=$m[1];
			#echo $mms;
			@mail($mms,$title,$content);
			file_put_contents('log.opera.txt',"$mms\n$title\n$content");
		}else{
			$sb->sblog($purl['url'],"Could not scrape MMS email address",1);
			return;
		}


		/*
		$form=$sb->find_form($b2,'new_post');
		if(!$form){
			$sb->sblog($purl['url'],"Something went wrong",1);
			return;
		}
		$form=$sb->set_formfield($form,'title',$title);
		$form=$sb->set_formfield($form,'body',urlencode($content));
		
		$form['inputs'][]=array('name'=>'size','value'=>'SIZE');
		$form['inputs'][]=array('name'=>'font','value'=>'FONT');
		$form['inputs'][]=array('name'=>'color','value'=>'COLOR');
		$form['inputs'][]=array('name'=>'lang','value'=>'en-US');

		$form=$sb->unset_formfield($form,'save');
		$form=$sb->unset_formfield($form,'preview');

		print_r($form);
		#return;

		#$args="new_post=1&id=&key=$key&size=SIZE&font=FONT&color=COLOR&stat=Select text, and use the formatting tools given above.&title=$title&excerpt=&body=$content&urltitle=$title&urltitledate=&tags=&watch=1&sharing=Public&lang=en-GB&submit=Publish";

		$args=$sb->form2args($form);
		echo $args;

		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
		$b3 = curl_exec($ch);

		echo $b3;
		*/

	}
	function t09_sb_myoperacom_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/my.opera.com.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://my.opera.com/'.$acc['username'].'/blog/">'.$acc['blog'].'my.opera.com</a></li>';
	}

?>