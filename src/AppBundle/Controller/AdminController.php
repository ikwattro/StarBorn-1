<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Resources;
use JoranBeaufort\Neo4jUserBundle\Entity\User;

class AdminController extends Controller
{
    public function indexAction()
    {
       $em = $this->get('neo4j.graph_manager')->getClient();        

        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $user=$em->getRepository(User::class)->findOneBy('usernameCanonical', $this->getUser()->getUsernameCanonical());
            return $this->render(
                'AppBundle:Admin:admin.html.twig',array('user' => $user)
            );
        } else {
            $user=$em->getRepository(User::class)->findOneBy('usernameCanonical', $this->getUser()->getUsernameCanonical());
            return $this->render(
                'AppBundle:Dashboard:index.html.twig',array('user' => $user)
            );
        }
    }
    
    public function resetpgAction()
    {
            $em = $this->get('neo4j.graph_manager')->getClient();        
            $user=$em->getRepository(User::class)->findOneBy('usernameCanonical', $this->getUser()->getUsernameCanonical());
            
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $em = $this->getDoctrine()->getManager();
            $connection = $em->getConnection();
            
            $q= "DROP TABLE gameField;";
            $statement = $connection->prepare($q);
            $statement->execute();
            
            $q='CREATE TABLE gameField (rid SERIAL PRIMARY KEY, rast raster);';
            $statement = $connection->prepare($q);
            $statement->execute();
            
            $q ="  INSERT INTO 
                    gameField(rast) 
                VALUES(
                    ST_Tile(
                        ST_AddBand(
                            ST_MakeEmptyRaster( 1850, 1200, 2483800, 1301000, 200, -200, 0, 0, 2056)
                        , 1, '32BUI'::text, 0, 0)
                    , 10,10, TRUE, NULL)
                );";
            $statement = $connection->prepare($q);
            $statement->execute();
                
            $q=" CREATE INDEX gameField_rast_gist_idx ON gameField USING GIST (ST_ConvexHull(rast));";
            $statement = $connection->prepare($q);
            $statement->execute();
            
            $q ="UPDATE gameField SET rast = ST_AddBand(rast, 2, '32BUI'::text, 0, 0)";
            $statement = $connection->prepare($q);
            $statement->execute();

            $message = 'Game Field Raster Reset Success! If you changed raster cell size, remember to change polygon display in map.html.twig!';
            return $this->render('AppBundle:Admin:admin.html.twig',array('user' => $user, 'message' => $message));
        } else {
            $user=$em->getRepository(User::class)->findOneBy('usernameCanonical', $this->getUser()->getUsernameCanonical());
            return $this->render(
                'AppBundle:Dashboard:index.html.twig',array('user' => $user)
            );
        }
    }
}