<?php
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: access");
	header("Access-Control-Allow-Methods: POST");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

	// Library TesseractOCR
	require_once "../vendor/autoload.php";
	use thiagoalessio\TesseractOCR\TesseractOCR;
	use thiagoalessio\TesseractOCR\UnsuccessfulCommandException;

	// Get server METHOD
	$request_method = $_SERVER['REQUEST_METHOD'];
	
	// Routage of the API
	try {
		switch ($request_method) {
			case 'POST':
				$image = checkInput($_FILES["fileToUpload"]["name"]);
				getFile($image);
				break;
			default:
				// Invalid request
				header("HTTP/1.0 405 Method Not Allowed");
				break;
		}
	} catch (Exception $e) {
		$response =[
			"status" => "error",
			"message" => $e->getMessage(),
			"code" => $e->getCode()
		];
		sendJSON($erreur);
	}


	//Function to get the Image File
	function getFile($image){
		$target_file = $response = "";

		//Create folder Uploads if not exist
		$path = 'uploads';

		if (!is_dir($path)) 
		{
			mkdir($path);
			$target_dir = $path.'/';
		}
		else 
		{
			$target_dir = $path.'/';
		}

		if (!empty($_FILES["fileToUpload"])) {
			$target_file = $target_dir . basename($image);
			$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

			//Allow certain image format
			if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
				$response = msg('error','Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
			} else {
				//Check file's size
				if ($_FILES["fileToUpload"]["size"] <= 1000000) {
					$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
					//Check the file
					if ($check !== false) {
						//If everything is okay upload the file
						if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
							//Call the function ReadMe
							ReadMe($target_file);
						} else {
							$response = msg('error','Sorry, there was an error uploading your file.'
							);
						}
					} else {
						$response = msg('error','Your File is not an image.');
					}
				} else {
					$response = msg('error','Sorry, your file is too large (Less than 1Mo).');
				}
			}
		} else {
			$response = msg('error','This API requires an image file.');
		}
		sendJSON($response);
	}

	
	// Function ReadMe Image
	function ReadMe($target_file){
		try {
			if (!empty($target_file)) {
				$response = msg('success',(new TesseractOCR($target_file))
				->lang('eng')
				->run()
				);
				// Deleting the file from the folder
				unlink($target_file);
			} else {
				$response = msg('error','Error Processing Request. There is no uploaded file.');
			}
		} catch (UnsuccessfulCommandException $e) {
			$response = msg('error','No text read on this image. Sorry!');
			// Deleting the file from the folder
			unlink($target_file);
		}
		sendJSON($response);
	}

	// Message function
	function msg($status,$message = []){
    return array_merge([
        'status' => $status,
        'message' => $message
    ]);
	}

	// JSON function
	function sendJSON($infos)
	{
		if ($infos != null) {
			header("Access-Control-Allow-Origin: *");
			header("Content-Type: application/json");
			echo json_encode($infos, JSON_UNESCAPED_UNICODE);
		}
	}

	// SECURISATION
	function checkInput($data) 
    {
      $data = trim($data);
      $data = stripslashes($data);
      $data = htmlspecialchars($data);
			$data = strip_tags($data);
      return $data;
    }

?>