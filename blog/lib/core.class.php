<?php
/*
 *
 * @project 0xBlog
 * @author KinG-InFeT
 * @licence GNU/GPL
 *
 * @file core.class.php
 *
 * @link http://0xproject.netsons.org#0xBlog
 *
 */
ob_start();

include ("config.php");
		
if(!defined("__INSTALLED__"))
	die("Run <a href=\"install.php\">./install.php</a> for Installation 0xBlog!");

include_once("language.class.php");

$lang = new Language();

include("languages/".$lang->load_language());

include("lib/security.class.php");

class Core extends Security {
	
	const VERSION = '3.3.0';

	public function __construct () {
	
			include ("config.php");				
			include_once ("mysql.class.php");
			
			$this->sql = new MySQL ($db_host, $db_user, $db_pass, $db_name);
	}
	
	public function show_header($title) {
	global $lang;
	
		$this->config =  mysqli_fetch_assoc($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."config"));
		
		$this->title  = (!empty($title)) ? $this->VarProtect($title) : $this->config['title'];
	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<title><?php print $this->title; ?> ~ Blog</title>
		<META http-equiv="content-type" content="text/html; charset=iso-8859-1">
		<META NAME="GENERATOR" CONTENT="VIM ~ The Linux Free Editor">
		<META NAME="AUTHOR"    CONTENT="KinG-InFeT ~ http://0xproject.netsons.org/">
		<?php 
		if(file_exists("themes/".$this->config['themes']))
			print "<link rel = 'stylesheet' type = 'text/css' href = 'themes/".$this->config['themes']."'>\n"; 
		else
			die("<b>Problems loading the file for your theme.</b><br />
				Please check if the selected file in the theme really exist.<br />
				File: ".$this->config['themes']);
		?>
		<script   language="text/javascript">
		function captcha()  { 
			document.addcomment.captcha.focus()
		}
		
		function check()  {
			if(document.addcomment.captcha.value==0)   {
				alert("<?php print $lang['no_match_captcha']; ?>");
				document.addcomment.captcha.focus();
				return false;
			}
		}
		function reload_captcha(hash) {
			var rnd = String(Math.random()); // Anti-cache-loading
			span = document.getElementById('captcha');
			span.innerHTML = '<img src="lib/captcha.php?hash=' + hash + '&rnd=' + rnd + '">';
		}
		</script>
		</head>
		<body onLoad="return captcha();" />
		<div id="container">
		<!-- header -->
		<div id="header">
			<h1>
			<a href="index.php"><?php print $this->config['title']; ?></a></h1>
			<p><i><?php print $this->config['description']; ?></i></p>
			<br />
		</div>
		<!-- fine header -->
	<?php
	}
	
	public function Pagination ($numHits, $limit, $page)  {
	
		$numHits  = (int) $numHits;
		$limit    = (int) $limit;
		$page     = (int) $page;
		$numPages = ceil($numHits / $limit);
		
		if(($page > $numPages) && ($numPages > 0))
			$page = $numPages;
			
		if($page < 1)
			$page = 1;
		
		$offset = ($page - 1) * $limit;
		
		$ret = array();
		
		$ret['offset'] 		= $offset;
		$ret['limit'] 		= $limit;
		$ret['numPages']	= $numPages;
		$ret['page']		= $page;
		
		return $ret; 
    }	
	
	public function show_blog($page) {
	global $page;
	global $lang;
		
		$this->config = mysqli_fetch_assoc($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."config"));
		
		$total  = mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles ORDER by id DESC"));
		$pager 	= $this->Pagination($total, $this->config['limit'], $page); 
		$offset = $pager['offset'];
		$limit 	= $pager['limit'];
		$page 	= $pager['page'];
		
		$this->blog  = $this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles ORDER by id DESC LIMIT ".$limit." OFFSET ".$offset);
	
		print "\n<div id=\"wrapper\">"
		    . "\n<div id=\"content\">\n";
	    
	    if($total < 1)
	    	print "\n<p align=\"center\"><b>".$lang['no_items']."</b></p>";
	    	
		while($this->articles = mysqli_fetch_assoc($this->blog)) {
		
			$this->comments = mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."comments WHERE blog_id = '".$this->articles['id']."'"));
			
			$tmp = NULL;
		
			print "\n<p align=\"left\"><b># <a href=\"viewpost.php?id=".$this->articles['id']."\">".$this->articles['title']."</a></b></p>\n";
			print "<br />\n"
				. "<p>\n";
			
			$len = strlen($this->articles['post']) / 2;
		 	
			for ($i = 0 ; $i < $len ; $i++)
				$tmp .= $this->articles['post'][$i];
				
			//$tmp = str_replace("\n","<br />",$tmp);
			
			//estraggo l'email dell'autore dell'articolo
			$this->mail = mysqli_fetch_row($this->sql->sendQuery("SELECT email FROM ".__PREFIX__."users WHERE username = '".addslashes($this->articles['author'])."'"));
			
			//estraggo il nome della categoria
			$this->cat = mysqli_fetch_assoc($this->sql->sendQuery("SELECT cat_name FROM ".__PREFIX__."categories WHERE cat_id = ".addslashes($this->articles['cat_id'])));

			//BBcode
			include_once("lib/admin.class.php");
			$BBcode = new Admin();
			
			print $BBcode->BBcode(str_replace('[code]', '',$tmp))." ...<a href=\"viewpost.php?id=".$this->articles['id']."\">[".$lang['go_read']."]</a>\n</p>\n";
			
			print "\n<br /><br /><p align=\"right\"><b>".$lang['categories']."</b>: ".$this->cat['cat_name']
				. " ~ <b>".$lang['view'].":</b> ".number_format((int)$this->articles['num_read'])
				. " ~ <b>".$lang['date'].":</b>".$this->articles['post_date']
				. " ~ <b>".$lang['name_author'].":</b> <em><u><a href=\"mailto:".$this->mail[0]."\">".$this->articles['author']."</a></u></em></p>"
				. "\n<br /> ".$lang['comments'].":".$this->comments."<br />";
				
			print "\n<br /><br />\n<hr />";
		}
			
		// genero la lista delle pagine
		if($pager['numPages'] > 0) {
			print "\n<p align=\"center\">";
			
			for ($i = 1; $i <= $pager['numPages']; $i++) {  
		
				if ($i < $pager['numPages']) 
					print " <a href=\"index.php?page=".$i."\">[".$i."]</a> -";
				else
					print " <a href=\"index.php?page=".$i."\">[".$i."]</a>";
			}
			print "\n</p>";
		}
		print "\n</div>"
			. "\n</div>\n";
	}
	
	public function get_title($id) {
		
		$this->id = intval($id);
		
		$this->my_is_numeric($this->id);
		
		$this->get_title = mysqli_fetch_assoc($this->sql->sendQuery("SELECT title FROM ".__PREFIX__."articles WHERE id = '".$this->id."'"));
		
		return ($this->get_title['title'] ? $this->get_title['title'] : '404 - Not Found');
	}
	
	public function show_post_and_comments($id) {
	global $lang;
	
		print "\n<div id=\"wrapper\">"
		    . "\n<div id=\"content\">\n";   
		
		$this->id = intval($id);
		
		if(empty($this->id))
			die("<div id=\"error\"><h2>".$lang['id_not_exist']."</h2></div>");
		
		$this->my_is_numeric($id); 
		
		if(mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles WHERE id = '".$this->id."'")) != 1)
			die("<div id=\"error\"><h2>".$lang['article_not_exist']."</h2></div>");
		
		$this->post = mysqli_fetch_assoc($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles WHERE id = '".$this->id."'"));
		
		$num_read = $this->post['num_read'] + 1;
		$this->num_read = intval($num_read);
		$this->sql->sendQuery("UPDATE ".__PREFIX__."articles SET num_read = '".$this->num_read."' WHERE id = '".$this->id."'");
		
		//estraggo l'email dell'autore dell'articolo
		$this->mail = mysqli_fetch_row($this->sql->sendQuery("SELECT email FROM ".__PREFIX__."users WHERE username = '".addslashes($this->post['author'])."'"));
		
		//estraggo il nome della categoria
		$this->cat = mysqli_fetch_assoc($this->sql->sendQuery("SELECT cat_name FROM ".__PREFIX__."categories WHERE cat_id = ".addslashes($this->post['cat_id'])));
		
		//BBcode
		include_once("lib/admin.class.php");
		$BBcode = new Admin();
		
		//stampo il singolo post
		print "\n<p align=\"left\"><b>".$this->post['title']."</b></p>";
		print "\n<br />\n<p>\n".$BBcode->BBcode($this->post['post'])."</p>";
		print "\n<br /><br /><p align=\"right\"><b>".$lang['categories']."</b>: ".$this->cat['cat_name']." ~ <b>".$lang['view'].":</b> ".$this->post['num_read']." ~ <b>".$lang['date'].":</b> ".$this->post['post_date']." ~ <b>".$lang['name_author'].":</b> <em><u><a href=\"mailto:".$this->mail[0]."\">".$this->post['author']."</a></u></em></p>\n<br /><br />";
		print "\n>:<a href=\"viewpost.php?id=".$this->post['id']."&action=comment\">[".$lang['commit']."]</a><br /><br /><hr />";
		
		//form per aggiungere un commento
		if(@$_GET['action'] == 'comment') {
			$code = NULL;

			for ($i = 0; $i < 3; $i++)
				$code .= chr (rand (65,90));
		
			for ($i = 0; $i < 4; $i++)
				$code .= rand (0,9);
		
			$_SESSION['captcha'] = $code;

			$hash = md5 (rand (0,9999999));

			$_SESSION['hash'] = $hash;
			
			print "\n<br />"
				. "\n<form name=\"addcomment\" action=\"viewpost.php?id=".$this->id."&action=send_comment\" method=\"POST\" onSubmit=\"return check();\">"
				. "\n<b>".$lang['name'].":</b><br /><input type=\"text\" name=\"name\" /><br /><br />"
				. "\n<b>".$lang['commit'].":</b><br /><textarea name=\"comment\" cols=\"30\" rows=\"2\"></textarea><br /><br />"
				. "\n<span id=\"captcha\"><img src=\"lib/captcha.php?hash=".$hash."&rnd=".rand(0,9999)."\" /></span> - <a href=\"javascript:reload_captcha('".$hash."');\">Reload Captcha</a><br /><br />"
				. "\n".$lang['add_captcha_code'].":<br />"
				. "\n<input type=\"text\" name=\"captcha\" id=\"captcha\"><br /><br />"
				. "\n<input type=\"submit\" value=\"".$lang['send']."\"  />"
				. "\n</form>";
		}elseif(@$_GET['action'] == 'send_comment') {//aggiunta reale del commento
				$key_generate     = strtoupper($_SESSION['captcha']);
				$captcha          = strtoupper($_POST['captcha']);

				if($captcha != $key_generate)
					die( "<script>alert(\"".$lang['no_match_captcha']."\"); window.location=\"viewpost.php?id=".$this->id."&action=comment\";</script>");
					
				if(empty($_POST['name']) || empty($_POST['comment'])) //Controllo se i campi sono riempiti oppure no
					die( "<script>alert(\"".$lang['fill_camp']."\");</script>");
			
				if (strlen($_POST['comment']) > 500)
					die( "<script>alert(\"".$lang['long_comment']."\");</script>");
	
				$commento = $this->VarProtect( $_POST['comment'] );
				$name     = $this->VarProtect( $_POST['name']    );
				
				$this->log_ip = mysqli_fetch_assoc($this->sql->sendQuery("SELECT ip_log_active FROM ".__PREFIX__."config"));
				
				if($this->log_ip['ip_log_active'] == 0)
					$ip = 'NULL';
				else
					$ip = $_SERVER['REMOTE_ADDR'];

				//eseguo query di isnerimento
				$this->sql->sendQuery("INSERT INTO ".__PREFIX__."comments (blog_id, name, comment, ip) VALUES ('".$this->id."', '{$name}', '{$commento}', '{$ip}')");
				header("Location: viewpost.php?id=".$this->id);
			}

		$this->comments = $this->sql->sendQuery("SELECT * FROM ".__PREFIX__."comments WHERE blog_id = '{$id}'");

		//cascata di commenti per il post
		if(mysqli_num_rows($this->comments) < 0) {
			echo "\n<br /><br />\n<em>".$lang['no_comment']."</em><br />\n";
		}else{
			while($row = mysqli_fetch_assoc($this->comments)) {
				echo "\n<br /><b>".$lang['name'].":</b>".$row['name']."<br />"
					."\n<b> ".$lang['commit'].": </b>".$row['comment']."<br /><br />"; 
			}
		}
		
		print "\n</div>\n</div>\n";
	}
	
	public function show_menu($class) {
	global $lang;
	
		if($class == 'admin') {
			print "\n<!-- Admin menu -->"
				. "\n<div id=\"navigation\">"
				. "\n    <p><h2>Admin Menu</h2></p>"
				. "\n    <ul>"
				. "\n      <li><a href=\"index.php\">Home Page</a></li>"		
				. "\n      <li><a href=\"admin.php\">".$lang['list_items']."</a></li>"
				. "\n      <li><hr /></li>"				
				. "\n      <li><a href=\"admin.php?action=add_post\">".$lang['new_art']."</a></li>"
				. "\n      <li><a href=\"admin.php?action=edit_post\">".$lang['edit_art']."</a></li>"
				. "\n      <li><a href=\"admin.php?action=del_post\">".$lang['delete']."</a></li>"
				. "\n      <li><hr /></li>"
				. "\n      <li><a href=\"admin.php?action=add_admin\">".$lang['add_admin']."</a></li>"
				. "\n      <li><a href=\"admin.php?action=change_pass_admin\">".$lang['change_pwd_admin'] ."</a></li>"
				. "\n      <li><a href=\"admin.php?action=del_admin\">".$lang['del_admin']."</a></li>"	
				. "\n      <li><hr /></li>"
				. "\n      <li><a href=\"admin.php?action=add_category\">".$lang['add_category']."</a></li>"
				. "\n      <li><a href=\"admin.php?action=edit_category\">".$lang['mod_category'] ."</a></li>"
				. "\n      <li><a href=\"admin.php?action=del_category\">".$lang['del_category']."</a></li>"	
				. "\n      <li><hr /></li>"
				. "\n      <li><a href=\"admin.php?action=settings\">".$lang['setting']."</a></li>"				
				. "\n      <li><a href=\"admin.php?action=clear_blog\">".$lang['reset']."</a></li>"
				. "\n      <li><a href=\"admin.php?action=themes\">".$lang['theme_admin']."</a></li>"				
				. "\n      <li><a href=\"admin.php?action=updates\">".$lang['update']."</a></li>"				
				. "\n      <li><hr /></li>"								
				. "\n      <li><a href=\"admin.php?action=logout&security=".$_SESSION['token']."\">Logout</a></li>"																												
				. "\n    </ul>"
				. "\n  </div>"
				. "\n<!-- Admin menu -->";
		}else{	
			print "\n<!-- menu -->"
				. "\n<div id=\"navigation\">"
				. "\n    <p><h2>Menu</h2></p>"
				. "\n    <ul style=\"list-style-type:none;\">"
				. "\n      <li><a href=\"index.php\">Home Page</a></li>"
				. "\n      <li><a href=\"search.php\">".$lang['search']."</a></li>"
				. "\n      <li><a href=\"admin.php\">".$lang['admin']."</a></li>"
				. "\n    </ul>"
				. "\n    <hr />"
				. "\n    ".$lang['categories'].":<br />"
				. "\n <ul>";
			
			$this->query = $this->sql->sendQuery("SELECT * FROM ".__PREFIX__."categories");
			
			while($this->cat = mysqli_fetch_assoc($this->query)) {
				$this->num_art_for_cat = NULL;
				
				$this->num_art_for_cat = mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles WHERE cat_id = '".(int) $this->cat['cat_id']."'"));
				
				print "\n<li>&raquo; <a href=\"index.php?mode=view_cat&cat_id=".$this->cat['cat_id']."\">".$this->cat['cat_name']."</a> (".$this->num_art_for_cat.")</li>";
			}
			
			print "\n</ul>"
				. "\n</div>"
				. "\n<div id=\"list_art_most_read\">"
				. "\n<br />"
				. "\n  <div id=\"extra\">"
				. "\n <p><strong>".$lang['most_read']."</strong></p>"
				. "\n<ol>";
			
			$this->articles = $this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles ORDER BY num_read DESC LIMIT 6");
			
			while($this->article = mysqli_fetch_assoc($this->articles)) {
				print "\n<li><font size=\"2\">".$this->article['post_date']." -> <a href=\"viewpost.php?id=".$this->article['id']."\">".$this->article['title']."</a></font></li>";
			}
		
			print "\n</ol>"
				. "\n</div>"
				. "\n</div>"
				. "\n<!-- fine menu -->\n";
		}
	}
	
	public function show_footer() {
	
		$this->config  = mysqli_fetch_assoc($this->sql->sendQuery("SELECT footer FROM ".__PREFIX__."config"));
		print "\n<!-- footer -->"
			. "\n <div id=\"footer\">"
			. "\n\t<p align=\"left\" style=\"float: left;\">".$this->config['footer']."</p>"			
			. "\n\t<p align=\"right\">Powered By <a href=\"http://0xproject.netsons.org/#0xBlog\"><u><i>0xBlog</i></u></a> v ".Core::VERSION."</p>"
			. "\n</div>"
			. "\n<!-- fine footer -->"
			. "\n</div>"
			. "\n</body>"
			. "\n</html>";
	}
	
	public function search($text) {
	global $lang;
	
		$this->text = $this->VarProtect($text);
		$this->text = trim(addslashes($this->text));
		
		if(isSet($this->text) == FALSE && empty($this->text))
			print "<div id=\"error\">".$lang['error_search']."</div>";
		else
			print "<br /><br />".$lang['search'].": <b>".$this->text."</b>";
			
		$arr_txt = explode(" ", $this->text);
		
        $sql = "SELECT * FROM ".__PREFIX__."articles WHERE ";
        
        for ($i = 0; $i < count($arr_txt); $i++) {

            if ($i > 0)
                $sql .= " AND ";

            $sql .= "(title LIKE '%" . $arr_txt[$i] . "%' OR post LIKE '%" . $arr_txt[$i] . "%')";
        }
        
        $sql .= " ORDER BY id DESC";
        
        $query  = $this->sql->sendQuery($sql);
        $quanti = mysqli_num_rows($query);
        
        if ($quanti == 0) {
			print "\n<p>".$lang['no_result']."!</p><br /><b><a href=\"search.php\">".$lang['back']."</a></b>";
        }else{
        	print "\n<br /><p><b>".$lang['result'].": </b></p>";
        	
            for($i = 0; $i < $quanti; $i++) {
            
                $this->result = mysqli_fetch_assoc($query);
                
                $this->id     = intval($this->result['id']);
                $this->title  = htmlspecialchars($this->result['title']);

				print "\n<p># <i><u><a href=\"viewpost.php?id=".$this->id."\">".$this->title."</a></u></i></p>";
            }
        }
	}
	
	public function show_articles_cat($cat_id, $page) {
	global $page;
	global $lang;
		
		$this->cat_id = (int) $cat_id;
		
		$this->my_is_numeric($this->cat_id);
		
		$this->config = mysqli_fetch_assoc($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."config"));
		
		$total  = mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles WHERE cat_id = '".(int) $this->cat_id."' ORDER by id DESC"));
		$pager 	= $this->Pagination($total, $this->config['limit'], $page);
		$offset = $pager['offset'];
		$limit 	= $pager['limit'];
		$page 	= $pager['page'];
		
		$this->blog = $this->sql->sendQuery("SELECT * FROM ".__PREFIX__."articles WHERE cat_id = '".(int) $this->cat_id."' ORDER by id DESC LIMIT ".$limit." OFFSET ".$offset);
	
		print "\n<div id=\"wrapper\">"
		    . "\n<div id=\"content\">\n";
	    
	    if($total < 1)
	    	print "\n<p align=\"center\"><b>".$lang['no_items']."</b></p>";
	    	
		while($this->articles = mysqli_fetch_assoc($this->blog)) {
		
			$this->comments = mysqli_num_rows($this->sql->sendQuery("SELECT * FROM ".__PREFIX__."comments WHERE blog_id = '".(int) $this->articles['id']."'"));
			
			$tmp = NULL;
		
			print "\n<p align=\"left\"><b># <a href=\"viewpost.php?id=".$this->articles['id']."\">".$this->articles['title']."</a></b></p>\n";
			print "<br />\n"
				. "<p>\n";
			
			$len = strlen($this->articles['post']) / 2;
		 	
			for ($i = 0 ; $i < $len ; $i++)
				$tmp .= $this->articles['post'][$i];
			
			//estraggo l'email dell'autore dell'articolo
			$this->mail = mysqli_fetch_row($this->sql->sendQuery("SELECT email FROM ".__PREFIX__."users WHERE username = '".addslashes($this->articles['author'])."'"));
			
			//estraggo il nome della categoria
			$this->cat = mysqli_fetch_assoc($this->sql->sendQuery("SELECT cat_name FROM ".__PREFIX__."categories WHERE cat_id = ".addslashes($this->articles['cat_id'])));

			//BBcode
			include_once("lib/admin.class.php");
			$BBcode = new Admin();
			
			print $BBcode->BBcode($tmp)." ...<a href=\"viewpost.php?id=".$this->articles['id']."\">[".$lang['go_read']."]</a>\n</p>\n";
			
			print "\n<br /><br /><p align=\"right\"><b>".$lang['categories']."</b>: ".$this->cat['cat_name']." ~ <b>".$lang['view'].":</b> ".$this->articles['num_read']." ~ <b>".$lang['date'].":</b>".$this->articles['post_date']." ~ <b>".$lang['name_author'].":</b> <em><u><a href=\"mailto:".$this->mail[0]."\">".$this->articles['author']."</a></u></em></p>"
				. "\n<br /> ".$lang['comments'].":".$this->comments."<br />";
				
			print "\n<br /><br />\n<hr />";
		}
			
		// genero la lista delle pagine
		if($pager['numPages'] > 0) {
			print "\n<p align=\"center\">";
			
			for ($i = 1; $i <= $pager['numPages']; $i++) {  
		
				if ($i < $pager['numPages']) 
					print " <a href=\"index.php?mode=".htmlspecialchars($_GET['mode'])."&cat_id=".htmlspecialchars($_GET['cat_id'])."&page=".$i."\">[".$i."]</a> -";
				else
					print " <a href=\"index.php?mode=".htmlspecialchars($_GET['mode'])."&cat_id=".htmlspecialchars($_GET['cat_id'])."&page=".$i."\">[".$i."]</a>";
			}
			print "\n</p>";
		}
		print "\n</div>"
			. "\n</div>\n";
	}
		
}//end class

?>
