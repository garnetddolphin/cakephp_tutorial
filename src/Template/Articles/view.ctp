<h1><?= h($article->title) ?></h1>
<p><?= h($article->body) ?></p>
<p><?= h($article->tag_string)?></p>
<?php if($article->file_name){
		echo '<img src="' . $this->Url->build('/upload_img/') . $article->file_name . '" alt="'. $article->file_name . '" width="256px">';
	}
?>
<p>作成日時： <?= $article->created->format(DATE_RFC850) ?></p>
<p><?= $this->Html->link('Edit', ['action' => 'edit', $article->slug]) ?></p>