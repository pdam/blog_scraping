<?php

	$sites['Social Networks']['Facebook pages']['fields']=array(
                    'email'=>'',			// Email				[str]
                    'password'=>'',			// Password				[str]
                    'username'=>'',			// Username				[str]
                    'profile_id'=>'',		// Int					[str]
                    'post_random'=>'',		// Post random links	[0|1]
                    'post_frequency'=>'',	// Post every			[int]
                    'post_chance'=>'',		// % chance of posting	[1-100]
                    'post_all'=>'',			// Post every new post	[1|0]
                    'post_start'=>'',		// Start date			[timestamp]
                    'show_on_widget'=>'',	// Display on sidebar	[1|0]
                );
    $sites['Social Networks']['Facebook pages']['spin']=array('status');
    $sites['Social Networks']['Facebook pages']['site']='facebook.com';
    $sites['Social Networks']['Facebook pages']['signup']='http://www.facebook.com';

    function t09_sb_facebookpages($id,$acc,$sb,$fuzz=false){
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
            $status="$title $url";
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
                    $status=$sb->format($acc['type'],'status',$status,$id,$fuzz);
                break;
            }
        }

		$status=$sb->preprocess('status',$status,$acc);

        $ch = curl_init();
        if(method_exists($sb,'proxy'))$ch=$sb->proxy($ch);

        $url = "https://www.facebook.com/";
        $cookie = tempnam("tmp", "facebook");

		$shareurl='https://www.facebook.com/sharer.php?u='.urlencode($perma).'&t='.urlencode($status);
		$url='http://www.facebook.com/login.php?next='.urlencode($url);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $sb->ua());
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        $b1 = curl_exec($ch);

		$form=$sb->find_form($b1,'pass');
		if($form){
			$form=$sb->set_formfield($form,'email',$u);
			$form=$sb->set_formfield($form,'pass',$p);
			$form=$sb->set_formfield($form,'next',$shareurl);	
			$form=$sb->set_formfield($form,'timezone','-60');
			$args=$sb->form2args($form,false);

			// Login
			curl_setopt($ch, CURLOPT_URL, $form['attr']['action']);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			curl_setopt($ch, CURLOPT_TIMEOUT,30);
			$b2=curl_exec($ch);

			// Load page
			$url=($acc['username'])?$acc['username']:'profile.php?id='.$acc['profile_id'];
			$url='https://m.facebook.com/'.$url;

			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_HTTPGET,true);
			curl_setopt($ch, CURLOPT_TIMEOUT,30);
			$bb=curl_exec($ch);
			#echo htmlentities($bb);
			if(!$bb){
				$sb->sblog($purl['url'],"Facebook connection timed out.",1);
				return;
			}

			// Check for redirect
			$str='/;url=([^"]+)/';
			preg_match($str,$bb,$m);
			$redirect=$m[1];
			if($redirect){
				#echo $redirect.'<br/>';
				ob_start();
				curl_setopt($ch, CURLOPT_URL, $redirect);
				curl_setopt($ch, CURLOPT_TIMEOUT,30);
				echo curl_exec($ch);
				$bb=ob_get_contents();
				ob_end_clean();

				$actionbase=$redirect;
			}else{
				$actionbase=$url;
			}

			#print_r($sb->get_forms($bb));

			// Form action base
			$actionbase=array_shift(explode('/',$actionbase)).'//m.facebook.com';

			#$status=$sb->preprocess('status',$status,$acc);
			
			$form=$sb->find_form($bb,'message');

			if($form){
				$form=$sb->set_formfield($form,'message',$status);
			}else{
				$form=$sb->find_form($bb,'status');
				if($form){
					$form=$sb->set_formfield($form,'status',$status);
				}else{
					// No forms found
					$sb->sblog($purl['url'],"No forms found on FB Page. Check you have permission to post.",1);
					return;
				}
			}

			if($form){			
				
				$args=$sb->form2args($form,false);
				#print_r($form);
				#echo $actionbase.$form['attr']['action'];
				ob_start();
				curl_setopt($ch, CURLOPT_URL, $actionbase.$form['attr']['action']);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
				$b3 = curl_exec($ch);
				$b3=ob_get_contents();
				ob_end_clean();

				/*
				// Check for redirect
				$str='/Location: (.*)\b/';
				preg_match($str,$b3,$m);
				$redirect=$m[1];
				if($redirect){
					ob_start();
					curl_setopt($ch, CURLOPT_URL, $redirect);
					echo curl_exec($ch);
					$bb2=ob_get_contents();
					ob_end_clean();
				}
				*/
			}else{
				#echo htmlentities($bb);
				$sb->sblog($purl['url'],"No page status box found. Check permission to post to this page.",1);
			}
		}else{
				$sb->sblog($purl['url'],"Facebook connection timed out.",1);
		}
	}
	function t09_sb_facebookpages_widget($id,$acc,$sb){
		$link=($acc['username'])?$acc['username']:'profile.php?id='.$acc['profile_id'];
		$img=$sb->base.'/Icons/facebook.16x16.png';
		echo '<li><img src="'.$img.'" align="absmiddle"> &nbsp;<a href="http://www.facebook.com/'.$link.'">facebook.com'.(($acc['username'])?'/'.$acc['username']:null).'</a></li>';
	}

?>