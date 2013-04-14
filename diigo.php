<?php
   

	$sites['Social bookmarking']['Diigo']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Social bookmarking']['Diigo']['spin']=array('title','content');
	$sites['Social bookmarking']['Diigo']['site']='diigo.com';
	$sites['Social bookmarking']['Diigo']['signup']='https://delicious.com/register';
	function t09_sb_diigo($id,$acc,$sb,$fuzz=false){

		$u=$acc['user'];
		$p=$acc['password'];

		if($fuzz&&count($fuzz)){
			$isfuzz=true;
			$title=$fuzz['title'];
			//$content=t09_sb_sh($fuzz['text']);
			$content=substr(html_entity_decode(t09_sb_sh($fuzz['text'])),0,150).'...';
			$perma=$fuzz['url'];
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$url=$sb->profile_url($acc);
			$sb->sblog($url['url'],(($fuzz['rss'])?'RSS post':'Fuzz post').": {$url['title']}",1);
		}else{
			$post=get_post($id);
			$title=$post->post_title;
			$perma=get_permalink($id);
			if(method_exists($sb,'shorten'))$perma=$sb->shorten($perma);
			$content=substr(html_entity_decode(t09_sb_sh(apply_filters('the_content',$post->post_content))),0,150).'...';
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
	
		$perma=urlencode($perma);
		$title=urlencode(html_entity_decode($title));
		$content=urlencode(html_entity_decode($content));

		ob_start();
		$ch=curl_init();

		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$url = 'http://api2.diigo.com/bookmarks';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "url=$perma&title=$title&shared=yes&tags=&desc=$content");
		curl_setopt($ch, CURLOPT_USERPWD, "$u:$p");
		$buffer = curl_exec($ch);
		ob_end_clean();
		
		curl_close($ch);
	}
	function t09_sb_diigo_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/diigo.16x16.png';
		echo '<li><img src="'.$img.'" align="middle"> &nbsp;<a href="http://diigo.com/user/'.$acc['user'].'">diigo.com/user/'.$acc['user'].'</a></li>';
	}
?>