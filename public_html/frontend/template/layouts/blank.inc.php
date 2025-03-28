<!DOCTYPE html>
<html lang="{{language}}" dir="{{text_direction}}">
<head>
<title>{{title}}</title>
<meta charset="{{charset}}">
<meta name="description" content="{{description}}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?php echo document::href_rlink('app://frontend/template/css/variables.css'); ?>">
<link rel="stylesheet" href="<?php echo document::href_rlink('app://assets/litecore/css/framework.min.css'); ?>">
<link rel="stylesheet" href="<?php echo document::href_rlink('app://frontend/template/css/app.min.css'); ?>">
{{head_tags}}
</head>
<body>

{{notices}}
{{content}}

{{foot_tags}}
<script src="<?php echo document::href_rlink('app://assets/litecore/js/framework.min.js'); ?>"></script>
<script src="<?php echo document::href_rlink('app://frontend/template/js/app.min.js'); ?>"></script>

</body>
</html>