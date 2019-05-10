<?php
namespace App\Model\Table;

use Cake\Validation\Validator;
use Cake\ORM\Table;
use Cake\Utility\Text;

class ArticlesTable extends Table
{
	public function initialize(array $config)
	{
		$this->addBehavior('Timestamp');
	}

	public function beforeSave($event, $entity, $options)
	{
		if($entity->isNew() && !$entity->slug){
			$sluggedTitle = Text::slug($entity->title);
			//  スラグをスキーマで定義されている最大長の調節
			$entity->slug = substr($sluggedTitle, 0,191);
		}
	}
	public function validationDefault(Validator $validator)
	{
		$validator
			// title
			->allowEmptyString('title, false')
			->minLength('title', 10)
			->maxLength('title', 255)
			// body
			->allowEmptyString('body', false)
			->minLength('body', 10);
		return $validator;
	}
}
