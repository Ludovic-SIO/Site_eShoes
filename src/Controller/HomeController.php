<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods:['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, Request $request, PaginatorInterface $paginator ): Response
    {
        // Récupère le terme de recherche en GET (?q=...)
        $q = $request->query->get('q');

        if ($q) {
            // utilise le QueryBuilder retourné par le repository pour permettre la pagination
            $data = $productRepository->searchByTerm($q);
        } else {
            $data = $productRepository->createQueryBuilder('p')
                ->orderBy('p.name', 'ASC');
        }

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'q' => $q,
        ]);
    }

    #[Route('/home/product/{id}:show', name: 'app_home_product_show', methods:['GET'])]
    public function show(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $lastProducts = $productRepository->findBy([],['id'=>'DESC'],limit: 5);

        return $this->render('home/show.html.twig', [
            'product' => $product,
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
