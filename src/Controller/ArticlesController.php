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

			$this->log($article, LOG_DEBUG);

			// 変更：セッションから user_id をセット
			$article->user_id = $this->Auth->user('id');

			// デバッグログを表示
			$this->log($this->request->getData(['file_name']), LOG_DEBUG);

			// ファイルアップロード処理
			$this->log(WWW_ROOT, LOG_DEBUG);
			$dir = realpath(WWW_ROOT . "/upload_img");
			$this->log($dir, LOG_DEBUG);

            $limitFileSize = 1024 * 1024;
            try {
                $article['file_name'] = $this->file_upload($this->request->getData(['file_name']), $dir, $limitFileSize);
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
		$this->set('article',$article);
	}

	public function edit($slug)
	{
		$article = $this->Articles->
			findBySlug($slug)
			->contain('Tags')  //関連付けられた Tags を読み込む
			->firstOrFail();

		if($this->request->is(['post', 'put']))
		{
			$this->Articles->patchEntity($article,$this->request->getData(),[
				'accessibleFields' => ['user_id' => false]
			]);
			// ファイルのアップロード処理
			$dir = realpath(WWW_ROOT . "/upload_img");
            $limitFileSize = 1024 * 1024;
            try {
                $article['file_name'] = $this->file_upload($this->request->data['file_name'], $dir, $limitFileSize);
            } catch (RuntimeException $e){
                $this->Flash->error(__('ファイルのアップロードができませんでした.'));
                $this->Flash->error(__($e->getMessage()));
            }

			if($this->Articles->save($article)){
				$this->Flash->success(__('Your article has been updated.'));
				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('Unable to update your article.'));
		}
		$this->set('article', $article);
	}

	public function delete($slug)
	{
		$this->request->allowMethod(['post', 'delete']);

		$article = $this->Articles->findBySlug($slug)->firstOrFail();
		if($this->Articles->delete($article)){
			$this->Flash->success(__('The {0} article has been deleted.', $article->title));
			return $this->redirect(['action' => 'index']);
		}
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
	public function isAuthorized($user){
		$action = $this->request->getParam('action');
		// add 及び tags アクションは常にログインしているユーザーに許可されます。
		if(in_array($action, ['add', 'tags'])){
			return true;
		}

		// 他のすべてのアクションにはスラッグが必要です。
		$slug = $this->request->getParam('pass.0');
		if(!$slug){
			return false;
		}

		// 記事が現在のユーザーに属していることを確認します。
		$article = $this->Articles->findBySlug($slug)->first();
		return $article->user_id === $user['id'];
	}

	public function file_upload ($file = null,$dir = null, $limitFileSize = 1024 * 1024){
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
			$uploadFile = sha1_file($file["tmp_name"]) . "." . $ext;

			// ファイルの移動
			if (!@move_uploaded_file($file["tmp_name"], $dir . "/" . $uploadFile)){
				throw new RuntimeException('Failed to move uploaded file.');
			}

			// 処理を抜けたら正常終了
			//echo 'File is uploaded successfully.';

		} catch (RuntimeException $e) {
			throw $e;
		}
		return $uploadFile;
	}
}