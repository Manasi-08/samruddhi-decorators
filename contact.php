
<?php 
    session_start();
    include_once('admin/config.php');
	$errors = array(); 

    if (!function_exists('input_data')) {
        function input_data($data) {  
            $data = trim($data);  
            $data = stripslashes($data);  
            $data = htmlspecialchars($data);  
            return $data;  
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $name    = input_data($_POST["name"]);
        $email   = input_data($_POST["email"]);
        $contact = input_data($_POST["contact"]);
        $message = input_data($_POST["message"]);
        
        if (empty($_POST["name"])) {
            $errors['name'] = 'Name is required';  
        } elseif (!preg_match("/^[a-zA-Z ]{2,60}$/", $name)) {
            $errors['name'] = 'Please enter a valid name';
        }

        if (empty($_POST["email"])) {  
            $errors['email'] = 'Email is required'; 
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {  
            $errors['email'] = 'Please enter a valid email'; 
        }
          
        if (empty($_POST["contact"])) {  
            $errors['contact'] = 'Contact number is required'; 
        } elseif (!preg_match('/^[0-9]{10}$/', $contact)) {
            $errors['contact'] = 'Please enter a valid 10 digit contact number';
        }
            
        if (empty($_POST["message"])) {  
            $errors['message'] = 'Message is required'; 
        }                                                                                                                                                                           

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO contact (name, email, contact, message) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $contact, $message);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Your message has been sent successfully!";
                } else {
                    $errors['general'] = "Failed to send message. Please try again.";
                }
                mysqli_stmt_close($stmt);
                header('location: contact.php');
                exit();
            } else {
                $errors['general'] = "Database error. Please try again.";
            }
        }
    }
?>

<?php include 'header.php'; ?>
<?php include 'menu.php'; ?>          

<!--================================
=            Page Title            =
=================================-->

<section class="page-title bg-title overlay-dark">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="title">
					<h3>Contact Us</h3>
				</div>
				<ol class="breadcrumb p-0 m-0">
				  <li class="breadcrumb-item"><a href="index.php">Home</a></li>
				  <li class="breadcrumb-item active">Contact Us</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<!--====  End of Page Title  ====-->


<section class="section contact-form">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<div class="section-title">
					<h3> <span class="alternate">Contact Us</span></h3>
				</div>
			</div>
		</div>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] );?>" method="post" class="row">
			<?php if(isset($_SESSION['success'])) { ?>
                <div class="form-group col-12 mb-3">
                    <div class="alert alert-success">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                        <?php                    
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                    </div>
                </div>
            <?php } ?>
            
            <div class="col-md-6">
				<input type="text" class="form-control main" name="name" id="name" placeholder="Your Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
				<span class="error"> <?php if(isset($errors['name'])) echo $errors['name']; ?> </span> 
			</div>
			<div class="col-md-6">
				<input type="email" class="form-control main" name="email" id="email" placeholder="Your Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
				<span class="error"> <?php if(isset($errors['email'])) echo $errors['email']; ?> </span> 
			</div>
			<div class="col-md-12">
				<input type="text" class="form-control main" name="contact" id="contact" placeholder="Contact Number" value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
				<span class="error"> <?php if(isset($errors['contact'])) echo $errors['contact']; ?> </span>
			</div>
			<div class="col-md-12">
				<textarea name="message" id="message" class="form-control main" rows="6" placeholder="Your Message"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
				<span class="error"> <?php if(isset($errors['message'])) echo $errors['message']; ?> </span> 
			</div>
			<div class="col-12 text-center">
				<button type="submit" class="btn btn-main-md">Send Message</button>
			</div>
		</form>
	</div>
</section>

<!--================================
=            Google Map            =
=================================-->

<section class="map">
	<!-- Google Map -->
	<div id="map"></div>
	<div class="address-block">
		<h4>Samruddhi Decorators</h4>
		<ul class="address-list p-0 m-0">
			<li><i class="fa fa-home"></i><span>At post Shiroda, Tal. Vengurla<br>Dist: Sindhudurg, State: Maharashtra</span></li>
			<li><i class="fa fa-phone"></i><span>+91 9222510824</span></li>
		</ul>
		<a href="#" class="btn btn-white-md">Get Direction</a>
	</div>
</section>

<!--====  End of Google Map  ====-->
<?php include 'footer.php'; ?>

    

        
