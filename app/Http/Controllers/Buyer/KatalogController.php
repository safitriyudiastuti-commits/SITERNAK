<?php
 
namespace App\Http\Controllers\Buyer;
 
use App\Http\Controllers\Controller;
use Illuminate\View\View;
 
class KatalogController extends Controller
{
    public function index(): View
    {
        return view('buyer.katalog.index');
    }
}
