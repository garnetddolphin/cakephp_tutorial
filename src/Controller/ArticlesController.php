<?php
namespace App\Controller;

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
}