<?php
	$sites['Microblogs']['Plurk']['fields']=array(
					'user'=>'',				// Username				[str]
					'password'=>'',			// Password				[str]
					'post_random'=>'',		// Post random links	[0|1]
					'post_frequency'=>'',	// Post every			[int]
					'post_chance'=>'',		// % chance of posting	[1-100]
					'post_all'=>'',			// Post every new post	[1|0]
					'post_start'=>'',		// Start date			[timestamp]
					'show_on_widget'=>'',	// Display on sidebar	[1|0]
				);
	$sites['Microblogs']['Plurk']['spin']=array('status');
	$sites['Microblogs']['Plurk']['site']='plurk.com';
	$sites['Microblogs']['Plurk']['signup']='http://www.plurk.com/Users/showRegister';

	function t09_sb_plurk($id,$acc,$sb,$fuzz=false){
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
					$tpl=$sb->format($acc['type'],null,array('status'=>$status,'perma'=>$perma),$id,$fuzz);
					$status=$tpl['status'];
					$perma=$tpl['perma'];
				break;
			}
		}
		$status=$sb->preprocess('status',$status,$acc);

		$u=urlencode($u);
		$p=urlencode($p);
		$status=urlencode($status);

		$ch=curl_init();
		if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

		$ck=tempnam('','plurk');
		$url='http://www.plurk.com/Users/showLogin';
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_COOKIESESSION,true);
		curl_setopt($ch,CURLOPT_COOKIEJAR,$ck);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$ck);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:11.0a1) Gecko/20111116 Firefox/11.0a1');
		$b0=curl_exec($ch);	

		$str='@name="login_token" value="([^"]+)"@si';
		preg_match($str,$b0,$m);
		$token=$m[1];

		$args='nick_name='.urlencode($u).'&password='.urlencode($p).'&login_token='.urlencode($token).'&logintoken=1';
		$url='https://www.plurk.com/Users/login';
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$args);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_HEADER,1);
		$b1=curl_exec($ch);
		
		$inf=explode("\r\n",$b1);
		foreach($inf as $k=>$v){
			$data=explode(': ',$v);
			(array)$info[strtolower($data[0])]=$data[1];
		}
		if($info['location']){
			$url=$info['location'];
			#$url='http://www.plurk.com/t/English';
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$b2=curl_exec($ch);
			#die($b2);

			$str='@var SETTINGS = ({[^}]+})@';
			preg_match($str,$b2,$m);
			if($m[1])$settings=json_decode(stripslashes($m[1]));
			if($settings->user_id){
				$uid=$settings->user_id;
				$now=explode(' ',microtime());
				$date='"'.date('Y-m-d\Th:i:s.',$now[1]).substr(($now[0]*100000),0,3).'Z"';
				#2011-11-19T09:46:30.655Z
				
				$args='posted='.urlencode($date).'&qualifier=%3A&content='.$status.'&lang=en&no_comments=0&location=&uid='.$uid;
				#echo $args.'<br>';


				#die($args);
				$url='http://www.plurk.com/TimeLine/addPlurk';
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_POST,true);
				curl_setopt($ch,CURLOPT_POSTFIELDS,$args);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				$b3=curl_exec($ch);

				#die($b3);
				//$result=json_decode(stripslashes($b3));

			}else{
				$sb->sblog('',"Plurk: $u: cannot find userid");
			}

		}
	}
	function t09_sb_plurk_widget($id,$acc,$sb){
		$img=$sb->base.'/Icons/plurk.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://plurk.com/'.$acc['user'].'">plurk.com/'.$acc['user'].'</a></li>';
	}
?>