<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Plex extends CI_Model {
	
	// third party plex keys
	public $third_party = array('photos', 'music', 'video');
	
	// curl ressources
	public $ch;
	
	/**
	 * __construct function.
	 * Constructor...
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct($root_segment = '')
	{
		parent::__construct();
		$this->root		= $root_segment;
		$this->debug	= $this->config->item('debug_uri');
	}
	
	/**
	 * find function.
	 * find a single section, mainly to get directory views
	 * 
	 * @access public
	 * @param mixed $id. (default: null)
	 * @return void
	 */
	function find($url = '')
	{
		$sections = $this->load($this->root.$url);
		$return						= $this->get_attributes($sections);
		$return->content	= $this->get_childrens($sections);

		return $return;
	}
	
		/**
	 * items function.
	 * Get the content of a section, by it's id and view
	 * 
	 * @access public
	 * @param array $segments. (default: array())
	 * @return void
	 */
	public function find_by($segments = array())
	{
		$items = $this->load(implode('/', $segments));
		$return						= $this->get_attributes($items);
		$return->content	= $this->get_childrens($items);

		return $return;
	}

	
	/**
	 * third_party function.
	 * try to get third party sharing.
	 * ie: iphoto, itunes, apertume.
	 * 
	 * @access public
	 * @return void
	 */
	public function third_party()
	{
		// first, get root server, then loop throught
		// directory to find and get enabled sharing
		$folder = array();
		$root		= $this->get_childrens($this->load());
		
		foreach ($root as $key => $directory)
		{
			if (in_array($directory->key, $this->third_party))
			{
				$folder[] = $directory;
			}
		}
		return $folder;
	}
	
	/**
	 * directory function.
	 * 
	 * @access public
	 * @param string $url. (default: '')
	 * @return void
	 */
	public function directory_scan($url = '')
	{
		$xml		= $this->get_childrens($this->load($url));
		$folder	= array();
		foreach ($xml as $key => $directory)
		{
			$folder[$key]->directory = $directory;
			$folder[$key]->content	 = $this->get_childrens($this->load($url.$directory->key));
		}
		return $folder;
	}
	
	/**
	 * directory function.
	 * 
	 * @access public
	 * @param mixed $url
	 * @return void
	 */
	public function directory($url)
	{
		return $this->get_childrens($this->load($url));
	}
	
	/**
	 * get_childrens function.
	 * Get the content of a child node
	 * 
	 * @access public
	 * @param mixed $element
	 * @return void
	 */
	public function get_childrens($element)
	{
		$return = array(); $i = 0;
		
		foreach ($element->children() as $child)
		{
			if (isset($child->Media))
			{
				$return[$i] = $this->get_attributes($child);
				$return[$i]->media = $child->Media;
			}
			else
			{
				$return[$i] = $child->attributes();
			}
			$i++;
		}
		return $return;
	}
	
	/**
	 * get_attributes function.
	 * Pass attributes elements to an object
	 * 
	 * @access public
	 * @param mixed $element
	 * @return void
	 */
	public function get_attributes($element)
	{
		$return = new stdClass;
		
		foreach ($element->attributes() as $key => $attribute)
		{
			$return->{$key} = $attribute;
		}
		
		return $return;
	}
	
	/**
	 * normalize function.
	 * Normalise object used in view
	 * 
	 * @access public
	 * @param mixed $xml_obj
	 * @return void
	 */
	public function normalize($xml_obj)
	{
		$normalized						= $this->get_attributes($xml_obj);
		$normalized->content	= $this->get_childrens($xml_obj);
		
		return $normalized;
	}
	
	/**
	 * set_authentication_header function.
	 * Send headers to plex server
	 * 
	 * @access private
	 * @return void
	 */
	public function authentication_headers($as_query = false)
	{
		if (!isset($this->user) OR ! isset($this->pass))
		{
			$this->user	= $this->config->item('username');
			$this->pass	= $this->config->item('password');
			$this->pass = sha1(strtolower($this->user).sha1($this->pass));
			
		}
		if ($as_query == false)
		{
			$return = array('X-Plex-User: '.$this->user, 'X-Plex-Pass: '.$this->pass);
		}
		else
		{
			$return = (object)array('X-Plex-User' => $this->user, 'X-Plex-Pass' => $this->pass);
		}
		return $return;
	}
	
	/**
	 * 
	 * handle auth for myPlex
	 */
	public function myplex_auth(){
		$token = $this->session->userdata('AuthToken'); 
		if ($token){
			return $token;	
		}
		
		$headers = array("X-Plex-Client-Identifier"=>"PlexOverDev");
		$user	= $this->config->item('username');
		$password	= $this->config->item('password');
		
		$headers = array("X-Plex-Client-Identifier: abc");

		$ch = curl_init();
		$url = "https://my.plexapp.com/users/sign_in.xml";
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $user.":".$password);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
		$opts = array(
		CURLOPT_POSTFIELDS     => array(),    // this are my post vars 
		CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl 
		CURLOPT_SSL_VERIFYPEER => false,        // 
		);
		curl_setopt_array($ch, $opts);
		$xml = curl_exec($ch);
		$infos	= curl_getinfo($ch);
		$code		= '';
		
		if ($infos['http_code'] != 201)
		{
			if ($infos['http_code'] === 0)
			{
				$code = lang('error.offline');
				$infos['http_code'] = 404;
			}
			exit(show_error(
				lang('error.plex').': '.str_replace('h1', 'strong', ($xml) ? $xml : $code).'<p><em>'.$url.'</em></p>',
				$infos['http_code']
			));
		}else{
			$obj = @simplexml_load_string($xml);
			$at = "authentication-token";
			$token = (string)$obj->$at;
			$this->session->set_userdata("AuthToken", $token);
		}
		
		
		return $token;
	}
	
	/**
	 * load_xml function.
	 * Load PMS xml files with simplexml classs and curl
	 * 
	 * @access private
	 * @return void
	 */
	private function load_xml($url)
	{
		$auth_token = $this->myplex_auth();	
		
		if (! $this->ch) $this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$url .= "?X-Plex-Token=".$auth_token;
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->authentication_headers());
		$xml		= curl_exec($this->ch);
		$infos	= curl_getinfo($this->ch);
		$code		= '';
		if ($infos['http_code'] != 200)
		{
			if ($infos['http_code'] === 0)
			{
				$code = lang('error.offline');
				$infos['http_code'] = 404;
			}
			exit(show_error(
				lang('error.plex').': '.str_replace('h1', 'strong', ($xml) ? $xml : $code).'<p><em>'.$url.'</em></p>',
				$infos['http_code']
			));
		}
		
		return $xml;
	}

	/**
	 * load function.
	 * load the requested url in simplexml object
	 * 
	 * @access private
	 * @param mixed $url
	 * @return void
	 */
	public function load($url = '')
	{
		// build uri request
		$request = $this->plex_local.str_replace('//', '/', '/'.$url);
		
		$xml = $this->load_xml($request);
		$object = @simplexml_load_string($xml);
		// enble uri debugging
		if ($this->debug === true) 
		{
			echo '<pre>'.$request.'</pre>';
		}
		
		return $object;
	}
	
	
}