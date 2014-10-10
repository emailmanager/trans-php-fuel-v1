<?php

/**
 * Emailmanager Email Delivery library for Fuel
 *
 * @package		Emailmanager
 * @version		1.0
 * @author		Tom Schlick (tom@tomschlick.com)
 * @link		http://github.com/tomschlick/fuel-emailmanager
 * 
 */

namespace Emailmanager;

class Email_Emailmanager extends \Email_Driver {

	public function __construct($config) 
	{
		parent::__construct($config);
		\Config::load('emailmanager');
	}
	
	/**
	 * Prepares the message contents to be sent via emailmanager.
	 *
	 * @return	string	The message.
	 */
	protected function _prepare_message($type = 'text')
	{
		$return = $this->newline;
		if($type == 'text')
		{
			$return .= $this->_prep_quoted_printable($this->_word_wrap($this->text_contents));
		}
		else
		{
			$return .= $this->_prep_quoted_printable($this->_word_wrap($this->html_contents));
		}
		return $return;
	}

	/**
	 * Sends the email using the emailmanager email delivery system
	 * 
	 * @return boolean	True if successful, false if not.
	 */	
	protected function _send()
	{
		if (!function_exists('curl_init'))
		{
			$this->_debug_message('Could not load curl. Make sure curl is enabled for emailmanager to work.', 'error');
			return false;
		}
		
		$data['Subject'] = $this->subject;	
		$data['From'] = $this->sender;
		$data['To'] = implode(', ', $this->recipients);

		if (!empty($this->cc_recipients)) 
		{
			$data['Cc'] = implode(', ', $this->cc_recipients);
		}
		
		if (!empty($this->bcc_recipients)) 
		{
			$data['Bcc'] = implode(', ', $this->bcc_recipients);
		}

		if (!empty($this->text_contents)) 
		{
			$data['HtmlBody'] = $this->_prepare_message('text');
		}

		if (!empty($this->html_contents)) 
		{
			$data['TextBody'] = $this->_prepare_message('html');
		}

		if(count($this->attachments) > 0) 
		{
			foreach($this->attachments as $attachment)
			{
				$contents = '';
				$basename = '';
				if ($attachment['dynamic'] == true)
				{
					// TODO: Dynamic attachment handling
					$basename = $attachment['filename'];
					$contents = $attachment['contents'];
				}
				else
				{
					// TODO: File attachment handling
					$filename = $attachment['filename'];
					$basename = basename($filename);
					if ( ! file_exists($filename))
					{
						$this->_debug_message('Could not find the file '.$filename, 'warning');
					}
					else
					{
						$filesize = filesize($filename) + 1;
						if ( ! $fp = fopen($filename, 'r'))
						{
							$this->_debug_message('Could not read the file '.$filename, 'warning');
						}
						else
						{
							$contents = fread($fp, $filesize);
							fclose($fp);
						}
					}
				}
				if ( ! empty($contents))
				{
					$filename = $attachment['filename'];
					$filetype = is_array($attachment['filetype']) ? $attachment['filetype'][0] : $attachment['filetype'];
					
					$data['Attachments'][] = array(
						'Name'			=> $filename,
						'Content'		=> base64_encode($contents),
						'ContentType'	=> $filetype, 
					);
				}			
			}
		}

		$encoded_data = json_encode($data);
	

		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'X-Emailmanager-Server-Token: ' . \Config::get('emailmanager_api_key'),
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://trans.emailmanager.com/email');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$output = curl_exec($ch);

		if (curl_error($ch) != '') {
			show_error(curl_error($ch));
			return false;
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		$output = json_decode($output);

		if (intval($httpCode / 100) != 2) 
		{
			$this->_debug_message("Emailmanager Error - Response: {$output->Message}", 'error');
			return false;
		}
		
		return true;
	}
}
/* End of file packages/emailmanager/classes/email/emailmanager.php */