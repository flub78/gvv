<html>
<head>
<title>404 Page Not Found</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8">

<style type="text/css">

body {
background-color:	#fff;
margin:				40px;
font-family:		Lucida Grande, Verdana, Sans-serif;
font-size:			12px;
color:				#000;
}

#content  {
border:				#999 1px solid;
background-color:	#fff;
padding:			20px 20px 12px 20px;
}

h1 {
font-weight:		normal;
font-size:			14px;
color:				#990000;
margin:				0 0 4px 0;
}
</style>
</head>
<body>
	<div id="content">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message;
		echo "<p>Vous ne pouvez pas accéder à cette page. Elle peut être vérouillée pour maintenance, ou vous n'avez pas les droits correspondants"
		. " ou elle est réellement manquante. Si la situation perdure et qu'elle vous semble anormale contactez votre administrateur.</p>"; 
		?>
	</div>
</body>
</html>