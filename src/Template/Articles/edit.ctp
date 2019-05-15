<h1>記事の編集</h1>
<?php
	echo $this->Form->create($article, ['type' => 'file']);
	echo $this->Form->control('title');
	echo $this->Form->control('body', ['rows' => 3]);
	echo $this->Form->control('tag_string', ['type' => '$text']);
// $this->log($article, LOG_DEBUG);
// $this->log($article->file_name, LOG_DEBUG);
	// if($article->file_name){
	// 	echo '<img src="' . $this->Url->build('/upload_img/') . $article->file_name . '" alt="'. $article->file_name . '" width="256px">';
	// 	echo $this->Form->control('file_before', ["type" => 'hidden',
	// 											"value" => $article->file_name
	// 							]);
	// 	echo $this->Form->control('delete',['type' => 'submit',
	// 									  'name' => 'file_delete',
	// 									  'value' => 'delete']);
	// }
	if($article->file_name){
		$image_path = $this->Image->Return_Path($article->file_name);
		if(file_exists($image_path)){
			$base64_image = $this->Image->Base64_Image($image_path);
			$this->log($base64_image,LOG_DEBUG);
			$data = 'data:image/png;base64,'.$base64_image;
			$this->log($data,LOG_DEBUG);
			echo $this->Html->formatTemplate(
				'image', ['url' => trim($data) ]
			);
		}
	}
	echo $this->Form->file('file_name');
	echo $this->Form->button(__('Save Article'));
	echo $this->Form->end();
?>