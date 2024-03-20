import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";

@Injectable({ providedIn: "root" })
export class LocationSevice {
  constructor(private http: HttpClient) {}

  getStates(stateId?: number): Observable<any> {
    const url = stateId ? `states?stateId=${stateId}` : `states`;
    return this.http.get(url);
  }

  getCitiesByState(stateId: number) {
    return this.http.get("cities", { params: { stateId } });
  }

  getLocationByCep(cep: string) {
    return this.http.get("cep/" + cep);
  }
}
