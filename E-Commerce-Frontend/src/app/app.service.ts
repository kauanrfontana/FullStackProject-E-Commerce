import { Injectable } from "@angular/core";
import { Subject } from "rxjs";

@Injectable({ providedIn: "root" })
export class AppService {
  verifyMenuSubject: Subject<boolean> = new Subject<boolean>();
}
