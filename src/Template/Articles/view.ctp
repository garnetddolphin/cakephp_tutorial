<h1><?= h($article->title) ?></h1>
<p><?= h($article->body) ?></p>
<p><?= h($article->tag_string)?></p>
<br>
<?php
if($article->file_name){
  $image_path = $this->Image->Return_Path($article->user_id,$article->sha1_file_name);
  if(file_exists($image_path)){
    $base64_image = $this->Image->Base64_Image($image_path);
// $this->log($base64_image,LOG_DEBUG);
    $data = 'data:image/png;base64,'.$base64_image;
// $this->log($data,LOG_DEBUG);
    echo $this->Html->formatTemplate(
      'image', ['url' => trim($data) ]
    );
  }
}
?>

<hr>
<p>作成日時： <?= $article->created->format(DATE_RFC850) ?></p>
<p><?= $this->Html->link('Edit', ['action' => 'edit', $article->slug]) ?></p>