import { Component, OnInit } from '@angular/core';
import { Product } from 'src/app/shared/models/Product';
import { ProductsService } from '../../products.service';
import { ActivatedRoute, Router } from '@angular/router';
import { IBasicResponseData } from 'src/app/shared/models/IBasicResponse.interfaces';
import Swal from 'sweetalert2';
import { UserService } from 'src/app/shared/services/user.service';
import { User } from 'src/app/shared/models/User';

@Component({
  selector: 'app-view-product',
  templateUrl: './view-product.component.html',
  styleUrls: ['./view-product.component.scss']
})
export class ViewProductComponent implements OnInit {
  loadingProductData: boolean = false;
  loadingSellerData: boolean = false;
  loadingLocationData: boolean = false;

  product = new Product()
  seller = new User()

  constructor(private productsService: ProductsService, private userService: UserService, private router: Router, private route: ActivatedRoute) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      if (params['id']) {
        this.getProductData(params['id'])
      }
    })
  }

  getProductData(id: number) {
    this.loadingProductData = true;

    this.productsService.getProductById(id).subscribe({
      next: (res: IBasicResponseData<Product>) => {
        this.product.setProductData(res.data);
        this.loadingProductData = false;
        this.getSellerData(this.product.userId);
      },
      error: (err: Error) => {
        Swal.fire('Erro ao consultar produto!', err.message, 'error');
        this.loadingProductData = false;
      }
    })
  }

  getSellerData(id: number) {
    this.userService.getUserData(id).subscribe({
      next: (res: IBasicResponseData<User>) => {
        this.seller.setUserData(res.data)
        this.loadingSellerData = false;
      },
      error: (err: Error) => {
        Swal.fire('Erro ao consultar vendedor!', err.message, 'error');
        this.loadingSellerData = false;
      }
    })
  }

  verifyIsLoading(): boolean {
    return this.loadingLocationData || this.loadingProductData || this.loadingSellerData;
  }

  goBack() {
    this.router.navigate(["/products"]);
  }
}
