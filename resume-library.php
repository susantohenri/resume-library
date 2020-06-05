<?php 
/*
Plugin Name: Resume Library
URI: resume-library.com
Description: Resume Library Candidate Registration
Author: henrisusanto 
Version: 1.0
Author URI: https://github.com/susantohenri/resume-library
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

function resume_library_shortcode () {
	ob_start ();
	if ( isset( $_POST['resume_library_submit'] ) ) resume_library_form_submission_handler ();
	resume_library_form ();
	return ob_get_clean();
}

add_shortcode( 'resume_library_candidate_registration_form', 'resume_library_shortcode' );

function resume_library_form_elements () {
	return array (
		array (
			'name' => 'first_name',
			'type' => 'text',
			'label'=> 'First Name',
			'required' => true
		),
		array (
			'name' => 'last_name',
			'type' => 'text',
			'label'=> 'Last Name',
			'required' => true
		),
		array (
			'name' => 'home_town',
			'type' => 'text',
			'label'=> 'Home Town',
			'required' => true
		),
		array (
			'name' => 'email',
			'type' => 'text',
			'label'=> 'Email',
			'required' => true
		),
		array (
			'name' => 'phone',
			'type' => 'text',
			'label'=> 'Phone',
			'required' => true
		),
		array (
			'name' => 'zip_code',
			'type' => 'text',
			'label'=> 'Zip code',
			'required' => true
		),
		array (
			'name' => 'latest_job_title',
			'type' => 'text',
			'label'=> 'Latest job title',
			'required' => true
		),

		array (
			'name' => 'acceptable_job_types',
			'type' => 'select-multiple',
			'label'=> 'Acceptable Job Types',
			'required' => true,
			'options' => array ('Permanent', 'Contract', 'Temp', 'Part Time')
		),

		array (
			'name' => 'uploaded_resume',
			'type' => 'file',
			'label'=> 'Resume',
			'required' => true
		),
		array (
			'name' => 'authorized_work_united_states',
			'type' => 'checkbox',
			'label'=> 'Are you authorized to work in the United States ?',
			'required' => true
		),
		array (
			'name' => 'resume_library_submit',
			'type' => 'submit',
			'label'=> 'Submit',
			'required' => true
		),
	);
}

function resume_library_form () {
	echo '<p>Upload your resume today to be headhunted in 50 different industries and apply for 1000s of jobs with 1-click apply.</p>';
	echo '<p><small>* indicates required field</small></p>';
	echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="POST" enctype="multipart/form-data">';

	foreach ( resume_library_form_elements () as $field ) {
		$name = $field['name'];
		$type = $field['type'];
		$label = $field['required'] ? $field['label'] . ' :*' : $field['required'] . ' :' ;
		$required = $field['required'] ? 'required = "required"' : '';
		$value = isset ( $_POST[$name] ) ? esc_attr ( $_POST[$name] ) : '';

		switch ( $type ) {
			case 'file':
				echo "
					<p>
						<label>{$label}</label>
						<input type=\"file\" name=\"{$name}\" value=\"{$value}\" size=\"40\" {$required} />
						<small>
							<br/>
							Acceptable file types: doc, docx, pdf, txt, gif, jpg, jpeg, png.
							<br/>
							Maximum file size: 1mb.
						</small>
					</p>
				";
				break;
			case 'checkbox':
				echo "
					<p>
						<input type=\"checkbox\" name=\"{$name}\" value=\"{$value}\">
						<label>{$label}</label>
					</p>
				";
				break;
			case 'submit':
				echo "<input type=\"submit\" value=\"{$label}\" name=\"{$name}\" >";
				break;
			case 'select-multiple':
				$options = '';
				foreach ($field['options'] as $option) $options .= "<option>{$option}</option>";
				echo "
					<p>
						<label>{$label}</label>
						<select name=\"{$name}\" value=\"{$value}\" {$required} multiple=\"multiple\">
							{$options}
						</select>
					</p>
				";
				break;
			default:
				echo "
					<p>
						<label>{$label}</label>
						<input type=\"text\" name=\"{$name}\" value=\"{$value}\" size=\"40\" {$required} />
					</p>
				";
				break;
		}
	}

	echo '</form>';
}

function resume_library_form_submission_handler () {

	$values = array_map(function ($field) {
		return sanitize_text_field ( $_POST[$field['name']] );
	}, resume_library_form_elements ());

	$base64 = resume_library_uploaded_file_toBas64 ('uploaded_resume');

	if ( 0 === count ($base64['error']) ) {
		if ( isset ( $_POST['authorized_work_united_states'] ) ) {
			$values['uploaded_resume'] = $base64['data'];
			$response = resume_library_curl ($values);
			echo '<p>';
			echo json_encode($response);
			echo '</p>';
		}
		echo "<p>Thanks {$values['first_name']}! submission success.</p>";
	} else {
		echo '<p>';
		foreach ( $base64['error'] as $error ) echo "{$error} <br/>";
		echo '</p>';
	}
}

function resume_library_uploaded_file_toBas64 ($name) {
  $errors = array ();
  $allowed_ext = array ('doc', 'docx', 'pdf', 'txt', 'gif', 'jpg', 'jpeg', 'png');
  $file_name = $_FILES[$name]['name'];
  $file_ext = strtolower ( pathinfo ( $file_name, PATHINFO_EXTENSION ) );
  $file_size = $_FILES[$name]['size'];
  $file_tmp = $_FILES[$name]['tmp_name'];

  $type = pathinfo ($file_tmp, PATHINFO_EXTENSION);
  $data = file_get_contents( $file_tmp );

  if ( in_array ($file_ext, $allowed_ext ) === false) $errors[] = 'Extension not allowed';
  if ( $file_size > 1 * 1024 * 1024 ) $errors[]= 'File size must be not larger than 1MB';

  return array (
  	'error' => $errors,
  	'data' => array (
  		'resume_content_base64' => 'data:image/' . $type . ';base64,' . base64_encode($data),
  		'resume_filename' => $file_name,
  		'resume_filetype' => $file_ext
  	)
  );
}

function resume_library_curl ($data) {
	$curl = curl_init ();
	curl_setopt_array ( $curl, array(
	  CURLOPT_URL => 'https://api.resume-library.com/v1/candidate/registration',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => json_encode ($data),
	  CURLOPT_HTTPHEADER => array(
	    'Content-Type: application/json',
	    'Authorization: Basic OTc1MzMzOmFjYzJjZDhhNjViNQ=='
	  ),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

?>