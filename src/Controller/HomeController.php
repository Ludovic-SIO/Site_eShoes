<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods:['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'products' => $productRepository->findBy([],['name'=>"ASC"]),
            'categories' => $categoryRepository->findAll(),
        ]); 
    }

    #[Route('/home/product/{id}:show', name: 'app_home_product_show', methods:['GET'])]
    public function show(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $lastProducts = $productRepository->findBy([],['id'=>'DESC'],limit: 5);

        return $this->render('home/show.html.twig', [
            'products' => $product,
            'products'=> $lastProducts,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    
    #[Route('/home/product/subcategory/{id}/filter', name: 'app_home_product_filter', methods:['GET'])]
    public function filter($id, CategoryRepository $categoryRepository, SubCategoryRepository $subCategoryRepository): Response
    {
        $products = $subCategoryRepository->find($id)->getProducts();
        $category = $subCategoryRepository->find($id)->getCategory();
        $subCategory =$subCategoryRepository->find($id);

        return $this->render('home/filter.html.twig', [
            'products' => $products,
            'categories'=> $categoryRepository->findAll(),
            'category' => $category,
            'subCategory' => $subCategory,
        ]);
    }
}
