<h1>記事の編集</h1>
<?php
	echo $this->Form->create($article, ['type' => 'file']);
	echo $this->Form->input('title');
	echo $this->Form->input('body', ['rows' => 3]);
	echo $this->Form->input('tag_string', ['type' => '$text']);
$this->log($article, LOG_DEBUG);
// $this->log($article->file_name, LOG_DEBUG);
	if($article->file_name){
		echo $this->Form->input('file_before', ["type" => 'hidden',
												"value" => $article->file_name
								]);
		echo $this->Form->input('delete',['type' => 'submit',
										  'name' => 'file_delete',
										  'value' => 'delete']);
	}
	echo $this->Form->file('file_name');
	echo $this->Form->button(__('Save Article'));
	echo $this->Form->end();
?>