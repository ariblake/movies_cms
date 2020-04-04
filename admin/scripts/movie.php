<?php

function addMovie($movie){

    try{
        //Connect to the DB
        $pdo = Database::getInstance()->getConnection();

        //Validate the uploaded file
        $cover = $movie['cover'];
        $upload_file = pathinfo($cover['name']);
        $accepted_types = array('gif', 'jpg', 'jpeg', 'jpe', 'png', 'webp');

        //if file extension does not match any in $accepted_types
        if(!in_array($upload_file['extension'], $accepted_types)) {
            //display an error
            throw new Exception('Wrong file type');
        }

        //Move the uploaded file around (move from tmp path to the /images)
        $image_path = '../images/';

        //optional: randomize/hash file name before moving it over
        $generated_name = md5($upload_file['filename'].time());
        $generated_filename = $generated_name.'.'.$upload_file['extension'];
        $targetpath = $image_path.$generated_filename;

        if(!move_uploaded_file($cover['tmp_name'],$targetpath)){
            throw new Exception('Failed to move uploaded file, check permission');
        }

        //insert into DB (tbl_movies as well as tbl_mov_genre)
        $insert_movie_query = 'INSERT INTO tbl_movies(movies_cover, movies_title, movies_year, movies_runtime, movies_storyline, movies_trailer, movies_release)';
        $insert_movie_query .= 'VALUES(:movies_cover, :movies_title, :movies_year, :movies_runtime, :movies_storyline, :movies_trailer, :movies_release)';

        $insert_movie = $pdo->prepare($insert_movie_query);
        $insert_movie_result = $insert_movie->execute(
            array(
                ':movies_cover'=>$generated_filename,
                ':movies_title'=>$movie['title'],
                ':movies_year'=>$movie['year'],
                ':movies_runtime'=>$movie['run'],
                ':movies_storyline'=>$movie['story'],
                ':movies_trailer'=>$movie['trailer'],
                ':movies_release'=>$movie['release'],
            )
        );

        $last_uploaded_id = $pdo->lastInsertId();
        if($insert_movie_result && !empty($last_uploaded_id)){
            $update_genre_query = 'INSERT INTO tbl_mov_genre(movies_id, genre_id) VALUES(:movies_id, :genre_id)';
            $update_genre = $pdo->prepare($update_genre_query);

            $update_genre_result = $update_genre->execute(
                array(
                    ':movies_id' => $last_uploaded_id,
                    ':genre_id' => $movie['genre'],
                )
            );
        }

        //If all of above works, redircct user to index.php
        redirect_to('index.php');

    }catch(Exception $e){
        //otherwise return error message
        $error = $e->getMessage();
        return $error;
    }
    
}