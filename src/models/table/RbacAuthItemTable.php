<?php

namespace modava\auth\models\table;

use backend\components\MyModel;
use dosamigos\arrayquery\ArrayQuery;
use modava\auth\models\User;
use Yii;

/**
 * @property string $name
 * @property int $type
 * @property string $description
 * @property string $rule_name
 * */
class RbacAuthItemTable extends MyModel
{
    public static function tableName()
    {
        return 'rbac_auth_item';
    }

    public function rules()
    {
        return [
            [['name', 'rule_name', 'description'], 'safe']
        ];
    }

    public function afterDelete()
    {
        $cache = Yii::$app->cache;
        $keys = [];
        foreach ($keys as $key) {
            $cache->delete($key);
        }
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    public function getParentHasMany()
    {
        return $this->hasMany(self::class, ['name' => 'parent'])->viaTable('rbac_auth_item_child', ['child' => 'name'])->indexBy('name');
    }

    public function afterSave($insert, $changedAttributes)
    {
        $cache = Yii::$app->cache;
        $keys = [];
        foreach ($keys as $key) {
            $cache->delete($key);
        }
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
    }

    public static function getByName($name, $cache = null)
    {
        $cache = Yii::$app->cache;
        $key = 'redis-rbac-auth-item-get-by-name';
        $data = $cache->get($key);
        if ($data == false || (defined('YII2_CACHE') && YII2_CACHE === false) || $cache === false) {
            $query = self::find()->where([self::tableName() . '.name' => $name]);
            $data = $query->one();
            $cache->set($key, $data);
        }
        return $data;
    }

    public static function getRoleChildByCurrentUser($get_current_role = false, $exclude = [])
    {
        if (is_string($exclude)) $exclude = [$exclude];
        if (!is_array($exclude)) $exclude = [];
        if ($get_current_role === false) {
            $user = new User();
            $roleName = $user->getRoleName(Yii::$app->user->id);
            $exclude[] = $roleName;
        }
        $authManager = Yii::$app->getAuthManager();

        $result = Yii::$app->getAuthManager()->getAssignments(Yii::$app->user->id);
        foreach ($result as $roleName) {
            $roleNames = $roleName->roleName;
        }

        $items = $authManager->getChildRoles($roleNames);

        $query = new ArrayQuery($items);

        $data = $query->find();
        unset($data['loginToBackend']);
        foreach ($exclude as $e) {
            if (array_key_exists($e, $data)) unset($data[$e]);
        }
        return $data;
    }
}
