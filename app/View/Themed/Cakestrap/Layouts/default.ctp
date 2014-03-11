<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
?>
<?php echo $this->Html->docType('html5'); ?> 


<html>
	<head>
		<?php echo $this->Html->charset(); ?>
		<meta name="viewport" content="initial-scale = 1.0,maximum-scale = 1.0" />
		<title>
			<?php echo $cakeDescription ?>:
			<?php echo $title_for_layout; ?>
		</title>

		<?php
			echo $this->Html->meta('icon');
			
			echo $this->fetch('meta');

			echo $this->Html->css('bootstrap');
			echo $this->Html->css('main');

			if(isset($cssIncludes)) {
				foreach($cssIncludes as $css) {
					echo $this->Html->css($css);	
				}
			}

			echo $this->fetch('css');
			

			echo $this->Html->script('//code.jquery.com/jquery-1.10.2.min.js');
			echo $this->Html->script('//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.1.38/jquery.form-validator.min.js');
			
			echo $this->Html->script('libs/bootstrap.min');

			if(isset($jsIncludes)) {
				foreach($jsIncludes as $js) {
					echo $this->Html->script($js);
				}
			}
			
			echo $this->fetch('script');
		?>


	</head>

	<body data-grid-framework="b3" data-grid-color="blue" data-grid-opacity="0.1" data-grid-zindex="10" data-grid-gutterwidth="30px" data-grid-nbcols="12">

		<div id="main-container">
		
			<div id="header" class="container">

				<div class="navbar navbar-default navbar-fixed-top">
				    <div class="container">
			   		   <div class="navbar-header">
				        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
				          <span class="icon-bar"></span>
				          <span class="icon-bar"></span>
				          <span class="icon-bar"></span>
				        </button>
				        <a class="navbar-brand" href="#">Remind Me</a>
				    </div>

				      <div class="collapse navbar-collapse">
				        <ul class="nav navbar-nav navbar-right">
				          
								<?php if($this->Session->check('User')): ?>
								
								<li> <?php echo $this->Html->link('Logout','/Users/logout'); ?> </li>
								<li> <?php echo $this->Html->link('Settings','/Users/settings'); ?> </li>
								<li> <?php echo $this->Html->link('Add Reminders','/Reminder/add'); ?> </li>
								<li> <?php echo $this->Html->link('View Reminders','/Reminder/get'); ?> </li>
									
								<?php else: ?>
									
								<li id='login'><?php echo $this->Html->link('Login','/Users/login'); ?></li>
								<li><?php echo $this->Html->link('Register','/Users/register'); ?></li>

								<?php endif; ?>
				        </ul>
				      </div><!--/.nav-collapse -->
				    </div>
				  </div>

			</div><!-- /#header .container -->
			
			<div id="content" class="container">
				<?php echo $this->Session->flash(); ?>
				<?php echo $this->fetch('content'); ?>
			</div><!-- /#content .container -->
			
		</div><!-- /#main-container -->

		</div><!-- /.container -->
	</body>
</html>