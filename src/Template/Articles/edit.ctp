<h1>記事の編集</h1>
<?php
	echo $this->Form->create($article, ['type' => 'file']);
	echo $this->Form->control('title');
	echo $this->Form->control('body', ['rows' => 3]);
	echo $this->Form->control('tag_string', ['type' => '$text']);
$this->log('$article', LOG_DEBUG);
$this->log($article, LOG_DEBUG);
$this->log($article->user_id, LOG_DEBUG);
	if($article->file_name){
		$image_path = $this->Image->Return_Path($article->user_id,$article->sha1_file_name);
$this->log($image_path,LOG_DEBUG);
		if(file_exists($image_path)){
			$base64_image = $this->Image->Base64_Image($image_path);
			$data = 'data:image/png;base64,'.$base64_image;
			echo $this->Html->formatTemplate(
				'image', ['url' => trim($data) ]
			);
		}
		echo $this->Form->control('file_before', ["type" => 'hidden',
												"value" => $article->file_name
								]);
		echo $this->Form->control('sha1_file_name', ["type" => 'hidden',
												"value" => $article->sha1_file_name
								]);
		echo $this->Form->control('delete',['type' => 'submit',
										  'name' => 'file_delete',
										  'value' => 'delete']);
	}

	echo $this->Form->file('file_name');
	echo $this->Form->button(__('Save Article'));
	echo $this->Form->end();
?>