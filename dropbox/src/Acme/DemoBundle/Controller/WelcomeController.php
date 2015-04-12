<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dropbox as dbx;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once "Dropbox/autoload.php";

class WelcomeController extends Controller
{
    public function indexAction()
    {
        session_start();
        if(isset($_SESSION['dbx'])){
            return new RedirectResponse("/logged");
        }
        else{

        $appInfo = dbx\AppInfo::loadFromJsonFile("config.json");
        $webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
        //get url for permission access to dropbox account
        $authorizeUrl = $webAuth->start();
        $_SESSION['web']=$webAuth;

        //take permission to access to dropbox account
        echo "<script>window.open(\"$authorizeUrl\",\"_blank\", \"toolbar=yes, scrollbars=yes, resizable=yes, top=500, left=500, width=400, height=400\");</script>";


        return $this->render('AcmeDemoBundle:Welcome:index.html.twig');
        }
    }


    public function loggedAction(){

        session_start();
        if(isset($_SESSION['dbx'])){
            //already access
            $dbxClient=$_SESSION['dbx'];
            $folderMetadata = $dbxClient->getMetadataWithChildren("/");
            //list of folder and file
            $array=$folderMetadata['contents'];

            return $this->render('AcmeDemoBundle:Welcome:logged.html.twig',array('array'=>$array));

        }
        else
        {
            //ACCESS FOR THE FIRST TIME
            if(isset($_SESSION['web'])){
                $webAuth=$_SESSION['web'];
                $authCode=$_POST['key'];
                //GET THE ACCESS TOKEN FOR API REQUEST AND CREATE DBXCLIENT
                list($accessToken, $dropboxUserId) = $webAuth->finish($authCode);
                $dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
                $_SESSION['dbx']=$dbxClient;
                //GET LIST OF FOLDER AND FILE OF PRIMARY DIRECTORY
                $folderMetadata = $dbxClient->getMetadataWithChildren("/");
                $array=$folderMetadata['contents'];

                return $this->render('AcmeDemoBundle:Welcome:logged.html.twig',array('array'=>$array));
            }
            else{
                //IF WEBAUTHENTICATION DOESN'T EXIST
                return $this->render('AcmeDemoBundle:Welcome:error.html.twig');
            }

        }
    }



    public function uploadAction(){
session_start();
        //UPLOAD PAGE
        if(isset($_SESSION['dbx']))
        return $this->render('AcmeDemoBundle:Welcome:upload.html.twig');
       else
           return $this->render('AcmeDemoBundle:Welcome:error.html.twig');


    }


    public function sendfileAction()
    {
        define("UPLOAD_DIR", "uploads/");
        session_start();
        if (isset($_SESSION['dbx'])) {

        $dbxClient = $_SESSION['dbx'];

            //TAKE THE FILE
        $uploadfile = $_FILES['userfile']['tmp_name'];
        $f = fopen($uploadfile, "rb");

            //SEND TO DROPBOX SERVER
        $result = $dbxClient->uploadFile("/" . ($_FILES['userfile']['name']), dbx\WriteMode::add(), $f);
        fclose($f);

        return new RedirectResponse("/logged");
    }
else{
    //SHOW ERROR PAGE
    return $this->render('AcmeDemoBundle:Welcome:error.html.twig');


}

    }

    public function downloadAction(){
        session_start();

        if (isset($_SESSION['dbx'])) {
            $dbxClient = $_SESSION['dbx'];
            $name = $_GET['name'];
            //REMOVE SLASH AND TAKE THE NAME OF FILE
            $file=substr($name,strrpos($name,"/")+1,strlen($name)-strrpos($name,"/"));
            //OPEN FILE
            $f = fopen($file, "w+b");
            //DOWNLOAD FILE FROM DROPBOX
            $fileMetadata = $dbxClient->getFile($name, $f);
            fclose($f);


            //SAVE FILE IN CLIENT SIDE (THE $FILE NOT THE $NAME)
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($file));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            }

        }else
        {
            return $this->render('AcmeDemoBundle:Welcome:error.html.twig');

        }



    }


    public function listAction(){
        session_start();

       if (isset($_SESSION['dbx'])) {
            //already access
            $dbxClient=$_SESSION['dbx'];
            $path=$_GET['name'];

            $folderMetadata = $dbxClient->getMetadataWithChildren($path);
            //list of folder and file
            $array=$folderMetadata['contents'];

            return $this->render('AcmeDemoBundle:Welcome:logged.html.twig',array('array'=>$array));

        }
        else{
            return $this->render('AcmeDemoBundle:Welcome:error.html.twig');
        }


    }

}
