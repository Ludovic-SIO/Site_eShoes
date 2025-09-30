<?php

namespace App\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(
    fields:['name'],
    message:'Ce nom de produit existe déja',
    errorPath:'name',
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * @var Collection<int, SubCategory>
     */
    #[ORM\ManyToMany(targetEntity: SubCategory::class, inversedBy: 'products')]
    private Collection $subCategories;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $stock = null;

    /*
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?self $product = null;
    */

    /**
     * @var Collection<int, self>
     */
    /*
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $products;


    #[ORM\Column]
    private ?int $qte = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
    */

    /**
     * @var Collection<int, AddProductHistory>
     */
    #[ORM\OneToMany(targetEntity: AddProductHistory::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $addProductHistories;


    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
        //$this->products = new ArrayCollection();
        $this->addProductHistories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, SubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategory $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        $this->subCategories->removeElement($subCategory);

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getProduct(): ?self
    {
        return $this->product;
    }

    public function setProduct(?self $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(self $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setProduct($this);
        }

        return $this;
    }

    public function removeProduct(self $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProduct() === $this) {
                $product->setProduct(null);
            }
        }

        return $this;
    }

    /*    
    public function getQte(): ?int
    {
        return $this->qte;
    }

    public function setQte(int $qte): static
    {
        $this->qte = $qte;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
        */

    /**
     * @return Collection<int, AddProductHistory>
     */

    /**
     * @return Collection<int, AddProductHistory>
     */
    public function getAddProductHistories(): Collection
    {
        return $this->addProductHistories;
    }

    public function addAddProductHistory(AddProductHistory $addProductHistory): static
    {
        if (!$this->addProductHistories->contains($addProductHistory)) {
            $this->addProductHistories->add($addProductHistory);
            $addProductHistory->setProduct($this);
        }

        return $this;
    }

    public function removeAddProductHistory(AddProductHistory $addProductHistory): static
    {
        if ($this->addProductHistories->removeElement($addProductHistory)) {
            // set the owning side to null (unless already changed)
            if ($addProductHistory->getProduct() === $this) {
                $addProductHistory->setProduct(null);
            }
        }

        return $this;
    }
   
}
