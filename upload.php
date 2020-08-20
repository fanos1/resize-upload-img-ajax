<?php
session_start();

	
$errors = array();
$output = '';
$count = 0; // count how many receipts uploaded
$upload_dir = '';


try {
	include __DIR__ . '/../../DbConnection.php';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') 	
	{		
		//check if the form submited is our own form
	    if (!isset($_POST['formtoken1']) || $_POST['formtoken1'] !== $_SESSION['formtoken1']) {
	        //$formtoken should always be set, if it is not set, create error
	        exit('The form submited is not valid. Please reload page and try again');
	    }
	    if (!empty($_POST['med'] )) { //!empty means bots must have populated form submited 	        
	        exit('The form submited is not valid. Med');
	    }

	    // Validate <imput>s.. validates number with 2 decimal
		if (!preg_match ('/^\d*(\.\d{0,2})?$/i',  $_POST['amount1']) ) {
			$errors['amount1'] = 'Το ποσό δεν είναι έγκυρο';			
		} else {
			// <input> is in Decimal because users find it is easier to input i.e. 3.40
			// €3.40 x 100 => 340			
			$amount1 = $_POST['amount1'] * 100; // we store int in DAtabase, not Decimal
		}

		// Validate date, if the format Not valid create Error. 
		// if user submited a receipt with date of April, we want to store that receipt's img in /April/ dir
		if (!preg_match ('/^[0-9-]{2,50}$/i', $_POST['receipt_date1'] )) {			
			$errors['receipt_date1'] = 'Η ημερομηνία apodiksi δεν είναι έγκυρη';
		} else {
			
			$dateString = $_POST['receipt_date1']; // $_POST['receipt_date1'] => 2020-04-07			
			$date = new DateTime($dateString); // create date object from the String

			// $month = $date->format('Y-m-d H:i:s'); // 2020-04-07 00:00:00 
			$month = $date->format('m'); 
			// echo "<h1> $month </h1>"; // 04

			switch ($month) {
				case 04:
					$upload_dir = "./img_april/";
					break;
				case 05:
					$upload_dir = "./img_may/";
					//echo "<h3>". $upload_dir ."</h3>";
					//exit('test');
					break;					
				case 06:
					$upload_dir = "./img_june/";
					break;	
				default:
					$upload_dir = "./receipt_gallery/";				
					break;
			}
		}	

		if (!preg_match ('/^[0-9]{1,20}$/i', $_POST['projectlist1'] )) {			
			$errors['projectlist1'] = 'projec1 not valid ';
		}

		// -----------------------
		// No validation Errors?, continue if so
		// ---------------------------
		if (empty($errors)) {

			//$base64Img = $_POST['image'];		
			$base64Img = $_POST['image'];
			$base64Img = str_replace('data:image/png;base64,', '', $base64Img);
			$base64Img = str_replace(' ', '+', $base64Img); // replace blanks into +
			$data = base64_decode($base64Img);

			// Load GD resource from binary $data
			$isItImg = imagecreatefromstring( $data);

			// Make sure that the GD library was able to load the image
			// This is important, because you should not miss corrupted or unsupported images
			// https://base64.guru/developers/php/examples/decode-image
			if (!$isItImg) {
				echo "<h3>Base64 value is not a valid image </h3>";    
				exit();
			} else {

				if (empty($upload_dir)) {
					die('upload dir is Empty');
				} else {

					$newName = rand();
					$file = $upload_dir. $newName .".png";
					/* file_put_conten() will put the originally uploaded file in /dir/
					 * this is less safe. instead, use imagepng() to create new img on Server			
					 * Save the GD resource as PNG in the best possible quality (no compression)
					 * This will strip any metadata or invalid contents (including, the PHP backdoor)
					 * To block any possible exploits, consider increasing the compression level
					 */
					$success = imagepng($isItImg, $file, 3); // Output a PNG image to either the browser or a file
					// exit($file); // ./img_april/810176624.png

					if ($success) 
					{
						$q = " INSERT INTO receipts (amount, receipt_date, proj_id, image) 
						VALUES ( :a, :receiptDate, :projId, :image)";

		            	$stmt = $pdo->prepare($q);                 
		                $stmt->bindParam(':a', $amount1);
		                $stmt->bindParam(':receiptDate', $_POST['receipt_date1'] );
		                $stmt->bindParam(':projId', $_POST['projectlist1']);
		                $stmt->bindParam(':image', $file);
		                $stmt->execute();
		                $output .= '<div class="alert alert-success">receipt1 was successfully uploaded </div>';

						$data = array(
							'status' => 'success',
							'message' => 'Image was created in Directory !'					
						);
						echo json_encode($data);
						exit();
					}
				}				
			}
 
		} 
		else  // if !empty(errors)
		{
			$data = array(
				'status' => 'error',
				'message' => 'Validation Errors! please make sure all fields are valid'				
			);
			echo json_encode($data);
			exit();
		} 		

	} // $_POST
	
} catch (PDOException $e) {
	$message = 'Database Error: ' . $e->getMessage() .' in '. $e->getFile() . ':' . $e->getLine();
}



$_SESSION['formtoken1'] = md5(uniqid(rand(), true));
$formToken1 = $_SESSION['formtoken1'];

?>



<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
	<link rel="stylesheet" href="js/magnific/magnific-popup.css">
	<link rel="stylesheet" href="css/main2.css">
	<link rel="stylesheet" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">    
</head>

<body>
	<div class="container" id="errors">
		<div class="col-12">
		<?php 
			echo $output;
		 	foreach ($errors as $key => $value) {
		 		echo '<div class="alert alert-danger">'.$value. '</div>';	 		
		 	}
		 ?>
		 <div> <a href="/index.html"><strong>&#9776;  Home </strong></a> </div>
		</div>
	</div>

	<div class="container" id="form">	
		<div class="col-6">
		<form method="post" enctype="multipart/form-data" id="myform" action="" accept-charset="utf-8">

			<input type="hidden" name="formtoken1" value="<?php echo $_SESSION['formtoken1']; ?>" />   
	        <p class="hp" style="display: none;"> <input type="text" name="med" id="med" value=""> </p>
					
			<fieldset>
				<legend><strong>Λεπτομέρειες σχετικά με την απόδειξη </strong></legend>
				<input type="hidden" name="from[]" value="receipt1">				                

				 <legend>ποσό - amount? </legend>			                                       
	            <input class="form-control" type="text" name="amount1" placeholder="3.50" />

	            <legend>Recipt Date </legend>
	            <input type="date" class="form-control" name="receipt_date1" value="2020-07-01" min="2020-07-01" max="2020-12-31" />
	            
	            <label for="projects">Proje</label>
				<select name="projectlist1" class="form-control">
				  <option value="1">Hugo</option>
				  <option value="2">Vigo</option>
				  <option value="3">Mangusta</option>
				  <option value="4">Bremen</option>
				</select>
			
				<br/>
				<input type="file" name="inputImgFile" id="inputImgFile" accept="image/x-png, image/gif, image/jpeg, image/jpg" />
			</fieldset>	
			<br/>
			<!-- <input type="submit" id="btn" value="Gonder &rarr;" class="btn btn-success" /> -->
		</form>
		</div>		
		<div class="col-6">			
			<div id="message"> </div>  
			<input type="button" value="Submit Image" id="button_resize" class="btn btn-success" />
		</div>
	</div>


	<div class="container">
		<div class="col-12">		
			<img src="" id="resizedimage" class="img-responsive">
		</div>
	</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script src="js/magnific/jquery.magnific-popup.min.js"></script>

<script type="text/javascript">     
$(document).ready( function () {

	// <canvas id="canvas" width="5" height="5"></canvas>
	//var canvas = document.getElementById('canvas');
	//var dataURL = canvas.toDataURL();
	//console.log(dataURL);// "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNby	

	function ResizeImage(evt) 
	{
		if ( window.FileReader ) 
        {	
		    var filesToUpload = document.getElementById('inputImgFile').files;
		    var file = filesToUpload[0];
		    var new_img = document.createElement("img");
		    
		    if(file) 
		    {
			    var reader = new FileReader();
		    	
		    	// Set the image once loaded into file reader
			    reader.onload = function(e) 
			    { 
			    	new_img.src = e.target.result;
			    	var datauriF = e.target.result;

			    	new_img.onload = function () {

			            var canvas = document.createElement("canvas");
			            
			            var ctx = canvas.getContext("2d");
			            ctx.drawImage(new_img, 0, 0);

			            var MAX_WIDTH = 600;
			            var MAX_HEIGHT = 800;
			            var width = new_img.width;
			            var height = new_img.height;

			            if (width > height) {
			                if (width > MAX_WIDTH) {
			                    height *= MAX_WIDTH / width;
			                    width = MAX_WIDTH;
			                }
			            } else {
			                if (height > MAX_HEIGHT) {
			                    width *= MAX_HEIGHT / height;
			                    height = MAX_HEIGHT;
			                }
			            }
			            canvas.width = width;
			            canvas.height = height;
			            var ctx = canvas.getContext("2d");
			            ctx.drawImage(new_img, 0, 0, width, height);

			            var dataurl = canvas.toDataURL("image/png");

			            // -------- Send to Server ----------
			            var formData = new FormData($("#myform")[0]);			            
			            formData.append("image", dataurl);
			            formData.append("info", "lah_de_dah");


			            $.ajax({
			                url: "/hr/upload.php",
			                type: "POST",
			                //data: fd,
			                data: formData,
			                cache: false,
			                contentType: false,
			                processData: false,			                
			                success: function(data) {
			                    //$('#form_photo')[0].reset();
			                    //location.reload();
			                    console.log(data); // {"status":"success","message":"Item added to bask"} // STRING
								//console.log(data.message); // not working, we need to parse data coming from Server			  
								  
								// if you want data returned from Server, parse it with parseJSON()
								// Also, make sure you encode() and echo encoded the data in the Server
								// var parsedData = jQuery.parseJSON(data); 						  
								// console.log(parsedData); 
								$('div#message').append('<div class="alert alert-success">success! img uploaded. </div>');
			                },
			                error: function (data) {
								console.log(data);
								$('div#message').append('<div class="alert alert-danger"> Error!. </div>');
							}

			            });
			    	};//img.Load()

			    }	    
			    // Load files into file reader
			    reader.readAsDataURL(file);

		    }// if (file)

        } else { alert('The File APIs are not fully supported in this browser.'); }
	}


	var resizeButton = document.getElementById("button_resize");

	resizeButton.addEventListener("click", function (evt) {
		ResizeImage(evt); //call to resize
	});


});
</script>

</body>
</html> 


