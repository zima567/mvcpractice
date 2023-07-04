<?php 

namespace Controller;

defined('ROOTPATH') OR exit('Access Denied!');
use \Model\Photo;
use \Core\Request;
use \Core\Session;

/**
 * Upload class
 */
class Upload
{
	use MainController;

	public function index()
	{
		$data['title'] = "Upload photo";
		$data['mode'] = 'new';

		$req = new Request;
		$ses = new Session;
		$photo = new Photo;

		if(!$ses->is_logged_in()){

			message("You need to login to edit an image");
			redirect('login');
		}

		if($req->posted())
		{
			$post_data = $req->post();
			if($photo->validate($post_data))
			{

				$post_data['date_created'] = date("Y-m-d H:i:s");
				$post_data['user_id'] = $ses->user('id');
				$post_data['image'] = "";
				$post_data['image1'] = "";
				$post_data['image2'] = "";
				$post_data['image3'] = "";
				
				$files = $req->files();
				$image_added = false;

				$folder = 'uploads/';
				if(!file_exists($folder))
				{
					mkdir($folder,0777,true);
					file_put_contents($folder.'index.php', "");
				}

				foreach ($files as $key => $file) {
				
					if(!empty($files[$key]['name']))
					{

						$allowed = ['image/jpeg','image/png','image/webp'];

						if(in_array($files[$key]['type'], $allowed))
						{
							$image_added = true;
							$post_data[$key] = $folder . time() . $files[$key]['name'];
							move_uploaded_file($files[$key]['tmp_name'], $post_data[$key]);
							
							$image = new \Model\Image;
							$image->resize($post_data[$key],1000);
							
						}

					}

				}
				
				$photo->insert($post_data);

				if(!$image_added){
					$photo->errors['image'] = "An image is required";
				}
			}

			$data['errors'] = $photo->errors;

			echo json_encode($data['errors']);
			die;
		}

		$data['photo'] = $photo;
		$this->view('upload',$data);
	}

	public function edit($id = null)
	{
		$data['title'] = "Edit photo";
		$data['mode'] = 'edit';

		$req = new Request;
		$ses = new Session;
		$photo = new Photo;

		if(!$ses->is_logged_in()){

			message("You need to login to edit an image");
			redirect('login');
		}

		$user_id = $ses->user('id');
		$data['row'] = $row = $photo->first(['id'=>$id,'user_id'=>$user_id]);

		if($req->posted() && $row)
		{
			$post_data = $req->post();
			$post_data['id'] = $row->id;

			if($photo->validate($post_data))
			{

				$post_data['date_updated'] = date("Y-m-d H:i:s");
				
				$files = $req->files();
				$folder = 'uploads/';
				if(!file_exists($folder))
				{
					mkdir($folder,0777,true);
					file_put_contents($folder.'index.php', "");
				}

				$allowed = ['image/jpeg','image/png','image/webp'];

				if(!empty($files['image']['name']))
				{
					if(in_array($files['image']['type'], $allowed))
					{
						$post_data['image'] = $folder . time() . $files['image']['name'];
						move_uploaded_file($files['image']['tmp_name'], $post_data['image']);
						
						$image = new \Model\Image;
						$image->resize($post_data['image'],1000);
						
						if(file_exists($row->image))
							unlink($row->image);

					}else{
						$photo->errors['image'] = "File type not supported";
					}

				}

				if(empty($photo->errors))
				{
					$photo->update($row->id,$post_data);
					redirect('photos');
				}
			}

			$data['errors'] = $photo->errors;
		}

		$data['photo'] = $photo;

		$this->view('upload',$data);

	}


	public function delete($id = null)
	{
		$data['title'] = "Delete photo";
		$data['mode'] = 'delete';

		$req = new Request;
		$ses = new Session;
		$photo = new Photo;

		if(!$ses->is_logged_in()){

			message("You need to login to delete an image");
			redirect('login');
		}

		$user_id = $ses->user('id');
		$data['row'] = $row = $photo->first(['id'=>$id,'user_id'=>$user_id]);

		if($req->posted() && $row)
		{
			$data = $req->post();
			$data['id'] = $row->id;
 
			$photo->delete($row->id);

			if(file_exists($row->image))
				unlink($row->image);

			if(file_exists($row->image1))
				unlink($row->image1);

			if(file_exists($row->image2))
				unlink($row->image2);

			if(file_exists($row->image3))
				unlink($row->image3);
			
			redirect('photos');

		}

		$data['photo'] = $photo;

		$this->view('upload',$data);

	}



}
