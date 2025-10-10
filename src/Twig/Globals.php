<?php
// src/Twig/AppExtension.php
namespace App\Twig;
 
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use App\Repository\CategoryRepository;
 
class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private $categoryRepository;
 
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }
 
    public function getGlobals(): array
    {
        return [
            'categories' => $this->categoryRepository->findAll()
        ];
    }
}
