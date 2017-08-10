<?php
namespace dvizh\gallery\controllers;

use yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Json;
use dvizh\gallery\models\Image;

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
            
    public function actionModal($id)
    {
        $model = $this->findImage($id);
        
        return $this->renderPartial('modalAdd', [
            'model' => $model,
            'post' => yii::$app->request->post(),
        ]);
    }

    public function actionWrite($id)
    {
        $model = $this->findImage($id);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->returnJson('success');
        }
        
        return $this->returnJson('false', 'Model or Image not found');
    }

    public function actionDelete($id)
    {
        $model = $this->findImage($id);
        $model->delete();
        
        return $this->returnJson('success');
    }
    
    public function actionSetmain($id)
    {
        $model = $this->findImage($id);
        $model->isMain = 1;
        $model->save(false);
        
        return $this->returnJson('success');
    }
    
    private function returnJson($result, $error = false)
    {
        $json = ['result' => $result, 'error' => $error];
        
        return Json::encode($json);
    }

    protected function findImage($id)
    {
        if(!$model = Image::findOne($id)) {
            throw new \yii\web\NotFoundHttpException("Image dont found.");
        }
        
        return $model;
    }
}