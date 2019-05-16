<?php
namespace App\Controller;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use RuntimeException;


class ArticlesController extends AppController
{
	public function initialize()
	{
		parent::initialize();

		$this->loadComponent('Paginator');
		$this->loadComponent('Flash'); // FlashComponentをインクルード
		$this->Auth->allow(['tags']);
	}
	public function index()
	{
		$articles = $this->Paginator->paginate($this->Articles->find());
		$this->set(compact('articles'));
	}

	public function view($slug)
	{
		$article = $this->Articles->findBySlug($slug)->firstOrFail();
		$this->set(compact('article'));
	}

	public function add()
	{
		$article = $this->Articles->newEntity();
		if($this->request->is('post')){
			$article = $this->Articles->patchEntity($article,$this->request->getData());
			// 変更：セッションから user_id をセット
			$article->user_id = $this->Auth->user('id');

			// ファイルアップロード処理
			// /Applications/XAMPP/htdocs/ImageFolder/[user_id]/以下
			// を確認して無ければディレクトリ作成
			// あったらその配下に画像を保存する
			$user_dir = '/Applications/XAMPP/htdocs/ImageFolder/' . $article['user_id'];
			if(file_exists($user_dir)){
				$this->log("exist", LOG_DEBUG);
			} else {
				$this->log("dont exist", LOG_DEBUG);
				mkdir($user_dir,0777);
			}
			$limitFileSize = 1024 * 1024;
			$this->log('getData([file_name])',LOG_DEBUG);
			$this->log($this->request->getData(['file_name'])['name'],LOG_DEBUG);
			try {
				$article['file_name'] = $this->request->getData(['file_name'])['name'];
				$article['sha1_file_name'] = $this->file_upload($this->request->getData(['file_name']), $user_dir, $limitFileSize);
			} catch (RuntimeException $e){
				$this->Flash->error(__('ファイルのアップロードができませんでした.'));
				$this->Flash->error(__($e->getMessage()));
			}

			if($this->Articles->save($article)){
				$this->Flash->success(__('Your article has been saved.'));
				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('Unable to add your article.'));
		}

		// タグのリストを取得
		$tags = $this->Articles->Tags->find('list');

		// ビューコンテキストにtagsをセット
		$this->set('tags', $tags);
		$this->set('article',$article);
	}

	public function edit($slug)
	{
		$article = $this->Articles->
			findBySlug($slug)
			->contain('Tags')  //関連付けられた Tags を読み込む
			->firstOrFail();

		if($this->request->is(['patch','post', 'put']))
		{
			 $this->Articles->patchEntity($article,$this->request->getData(),[
				// user_idの更新を無効化
				'accessibleFields' => ['user_id' => false]
			]);

			// ファイルアップロード処理
			// /Applications/XAMPP/htdocs/ImageFolder/[user_id]/以下
			// を確認して無ければディレクトリ作成
			// あったらその配下に画像を保存する
			$user_dir = '/Applications/XAMPP/htdocs/ImageFolder/' . $article['user_id'];
			if(file_exists($user_dir)){
				$this->log("exist", LOG_DEBUG);
			} else {
				$this->log("dont exist", LOG_DEBUG);
				mkdir($user_dir,0777);
			}
			// deleteボタンがクリックされたとき
			if(isset($this->request->data['file_delete'])){
				try {
					$del_file = $user_dir. $this->request->data["file_before"];
					// ファイル削除処理実行
					if($del_file->delete()){
						$article['file_name'] = "";
					} else {
						$article['file_name'] = $this->request->data['file_before'];
						throw new RuntimeException("ファイルの削除ができませんでした。");
					}
				} catch(RuntimeException $e){
					$this->Flash->error(__($e->getMessage()));
				}
			} else {
				// ファイルが入力されたとき
				if($this->request->getData(['file_name'])){
					$limitFileSize = 1024 * 1024;
					try {
						$article['file_name'] = $this->file_upload($this->request->getData(['file_name']), $user_dir, $limitFileSize);
						// ファイル更新の場合は古いファイルは削除
						if(isset($this->request->data['file_before'])){
							// ファイル名が同じ場合は削除を実行しない
							if($this->request->data['file_before'] != $article['file_name']){
								$del_file = $user_dir . $this->request->data["file_before"];
								if(!$del_file->delete()){
									$this->log("ファイル更新時に下記ファイルが削除できませんでした。", LOG_DEBUG);
									$this->log($this->request->data['"file_before'],LOG_DEBUG);
								}
							}
						}
					} catch (RuntimeException $e) {
						// アップロード失敗時、既に登録ファイルが有る場合はそれを保持
						if(isset($this->request->data['file_before'])){
							$article['file_name'] = $this->request->data["file_before"];
						}
						$this->Flash->error(__("ファイルのアップロードができませんでした。"));
						$this->Flash->error(__($e->getMessage()));
					}
				} else {
					// ファイルは入力されていないけど登録されているファイルが有るとき
					if(isset($this->request->data["file_before"])){
						$article['file_name'] = $this->request->data['file_before'];
					}
				}
				if($this->Articles->save($article)){
					$this->Flash->success(__('Your article has been updated.'));
					if(isset($this->request->data["file_delete"])){
						$this->set(compact('article'));
						 return $this->redirect(['action' => 'edit', $article['slug']]);
					} else {
						 return $this->redirect(['action' => 'index']);
					}
				} else {
					$this->Flash->error(__('Unable to update your article.'));
				}
			}
		}
		// タグのリストを取得
		$tags = $this->Articles->Tags->find('list');

		// ビューコンテキストにtagsをセット
		$this->set('tags', $tags);
		$this->set('article', $article);
	}

	public function delete($slug)
	{
		$this->request->allowMethod(['post', 'delete']);
		$article = $this->Articles->findBySlug($slug)->firstOrFail();
		$dir = realpath(WWW_ROOT . "upload_img");
		try {
			$del_file = new File($dir . "/" . $article->file_name);
			// ファイル削除処理実行
			if($del_file->delete()){
				$article['file_name'] = "";
			} else {
				throw new RuntimeException('ファイルの削除ができませんでした。');
			}
		} catch (RuntimeException $e) {
				$this->log($e->getMessage(), LOG_DEBUG);
				$this->log($article->file_name, LOG_DEBUG);
		}
		if($this->Articles->delete($article)){
			$this->Flash->success(__('The {0} article has been deleted.', $article->title));
		} else {
			$this->Flash->error(__('The article could not be deleted. Please try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}

	public function tags()
	{
		// 'pass'キーはCakePHPによって提供され、リクエストに渡された
		// すべてのURLパスセグメントを含みます。
		$tags = $this->request->getParam('pass');

		// ArticlesTable を使用してタグ付きの記事を検索します。
		$articles = $this->Articles->find('tagged', [
			'tags' => $tags
		]);

		// 変数ビューテンプレートのコンテキストに渡します。
		$this->set([
			'articles' => $articles,
			'tags' => $tags
		]);
	}
	public function isAuthorized($user)
	{
    $action = $this->request->getParam('action');
    // add および tags アクションは、常にログインしているユーザーに許可されます。
    if (in_array($action, ['add', 'tags'])) {
        return true;
    }

    // 他のすべてのアクションにはスラッグが必要です。
    $slug = $this->request->getParam('pass.0');
    if (!$slug) {
        return false;
    }

    // 記事が現在のユーザーに属していることを確認します。
    $article = $this->Articles->findBySlug($slug)->first();
    return $article->user_id === $user['id'];
	}

	public function file_upload ($file = null,$dir = null, $limitFileSize = 1024 * 1024){
		// $this->log("file_upload function now.", LOG_DEBUG);
		try {
			// ファイルを保存するフォルダ $dirの値のチェック
			if ($dir){
				if(!file_exists($dir)){
					throw new RuntimeException('指定のディレクトリがありません。');
				}
			} else {
				throw new RuntimeException('ディレクトリの指定がありません。');
			}

			// 未定義、複数ファイル、破損攻撃のいずれかの場合は無効処理
			if (!isset($file['error']) || is_array($file['error'])){
                throw new RuntimeException('Invalid parameters.');
            }

			// エラーのチェック
			switch ($file['error']) {
				case 0:
					break;
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException('No file sent.');
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException('Exceeded filesize limit.');
				default:
					throw new RuntimeException('Unknown errors.');
			}

			// ファイル情報取得
			$fileInfo = new File($file["tmp_name"]);

			// ファイルサイズのチェック
			if ($fileInfo->size() > $limitFileSize) {
				throw new RuntimeException('Exceeded filesize limit.');
			}

			// ファイルタイプのチェックし、拡張子を取得
			if (false === $ext = array_search($fileInfo->mime(),
											['jpg' => 'image/jpeg',
											 'png' => 'image/png',
											 'gif' => 'image/gif',],
											 true)){
				throw new RuntimeException('Invalid file format.');
			}

			// ファイル名の生成
			//$uploadFile = $file["name"] . "." . $ext;
			// $uploadFile = $file['name'];

			// tmpファイルのパスを取得
			$tmpPath = $file['tmp_name'];
			$this->log('$tmpPath',LOG_DEBUG);
			$this->log($tmpPath,LOG_DEBUG);

			// ファイルの拡張子を取得
			$path_parts = pathinfo($file['name']);
			$this->log('$path_parts',LOG_DEBUG);
			$this->log($path_parts,LOG_DEBUG);

			$extension = $path_parts['extension'];
			$this->log('$extension',LOG_DEBUG);
			$this->log($extension,LOG_DEBUG);


			// 画像のデータをSHA-1でハッシュ化してファイル名を作る
			$imgfilehash = hash_file("sha1", $tmpPath);
			$this->log('$imgfilehash',LOG_DEBUG);
			$this->log($imgfilehash,LOG_DEBUG);

			$imgfile = $imgfilehash . "." . $extension;
			$this->log('$imgfile',LOG_DEBUG);
			$this->log($imgfile,LOG_DEBUG);



			// 画像格納先のフルパスを生成
			$imgfilepath = $dir. '/' .$imgfile;
			$this->log('$imgfilepath',LOG_DEBUG);
			$this->log($imgfilepath,LOG_DEBUG);

			// 同じデータの画像が保存されているか確認
			// されていたらmoveしない
			if(!file_exists($imgfilepath)){
				// ファイルの移動
				if (!@move_uploaded_file($file['tmp_name'], $imgfilepath)){
					throw new RuntimeException('Failed to move uploaded file.');
				}
			}

		} catch (RuntimeException $e) {
			throw $e;
		}
		return $imgfile;
	}
}