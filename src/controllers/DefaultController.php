<?php
namespace dvizh\gallery\controllers;

use yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Json;

class DefaultController extends Controller
{
        public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'ajax' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->module->adminRoles
                    ]
                ]
            ]
        ];
    }

    
    public function actionIndex()
    {
        return $this->render('index');
    }
            
    public function actionModal()
    {
        $arr = $this->findImage();
        $post = \Yii::$app->request->post();
        
        if ($arr) {
            return $this->renderPartial('modalAdd', [
                'model' => $arr['image'],
                'post' => $post,
            ]);
        }
        
        return $this->returnJson('false', 'Model or Image not found');
    }

    public function actionWrite()
    {
        $arr = $this->findImage();
        
        if ($arr['image']->load(Yii::$app->request->post()) && $arr['image']->save()) {
            return $this->returnJson('success');
        }
        
        return $this->returnJson('false', 'Model or Image not found');
    }

    public function actionDelete()
    {
        $arr = $this->findImage();
        
        if ($arr) {
            $arr['model']->removeImage($arr['image']);
            return $this->returnJson('success');
        }
        
        return $this->returnJson('false', 'Model or Image not found');
    }
    
    public function actionSetmain()
    {
        $arr = $this->findImage();
        
        if ($arr) {
            $arr['model']->setMainImage($arr['image']);
            return $this->returnJson('success');
        }
        
        return $this->returnJson('false', 'Model or Image not found');
    }
    
    private function returnJson($result, $error = false)
    {
        $json = ['result' => $result, 'error' => $error];
        
        return Json::encode($json);
    }

    private function findImage()
    {
        $model = $this->findModel(Yii::$app->request->post('model'), Yii::$app->request->post('id'));
        
        foreach ($model->getImages() as $img) {
            if ($img->id == Yii::$app->request->post('image')) {
                 return $arr = ['model' => $model, 'image' => $img];
            }
        }
        
        return false;
    }

    private function findModel($model, $id)
    {
        $model = '\\'.$model;
        $model = new $model();

        return $model::findOne($id);
    }
}