<?php
namespace App\Filters;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface {
    public function before(RequestInterface $request, $arguments = null) {
        if (!session()->get('user_id')):
            $currentUrl = current_url();
            return redirect()->to('/login?url='. urlencode($currentUrl));
        endif;
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
