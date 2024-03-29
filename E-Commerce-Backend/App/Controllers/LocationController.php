<?php

namespace App\Controllers;

use App\DAO\LocationDAO;
use App\Models\Locations\{
    CityModel,
    CountryModel,
    NeighborhoodModel,
    StateModel,
    StreetAvenueModel
};

use GuzzleHttp\Exception\RequestException;
use Slim\Container;
use Slim\Http\{
    Request,
    Response
};
use GuzzleHttp\Client;

final class LocationController
{

    public function getStates(Request $request, Response $response, array $args): Response
    {
        $client = new Client();
        $stateId = $request->getParam("stateId");
        $apiUrl = LOCATION_PUBLIC_API . "/estados?orderBy=nome";
        try {
            if (!empty ($stateId)) {
                $apiUrl = LOCATION_PUBLIC_API . "/estados/{$stateId}";
            }

            try {
                $apiResponse = $client->request("GET", $apiUrl);
            } catch (RequestException $e) {
                throw new \Exception("Serviço de consulta indisponível no momento, tente novamente mais tarde!");
            }


            if ($apiResponse->getStatusCode() != "200") {
                throw new \Exception("Serviço de consulta indisponível no momento, tente novamente mais tarde!");
            }

            $states = json_decode($apiResponse->getBody()->getContents(), true);
            if (isset ($states["nome"])) {
                $states = [
                    "id" => $states["id"],
                    "name" => $states["nome"]
                ];
            } else {
                $states = array_map(function ($state) {
                    return [
                        "id" => $state["id"],
                        "name" => $state["nome"]
                    ];
                }, $states);
            }

            $response = $response->withStatus(200)->withJson([
                "data" => $states
            ]);

        } catch (\Exception $e) {
            $response = $response->withStatus(500)->withJson([
                "message" => $e->getMessage()
            ]);
        }

        return $response;
    }


    public function getCitiesByState(Request $request, Response $response, array $args): Response
    {
        $client = new Client();

        $stateId = $request->getParam("stateId");
        try {
            if (empty ($stateId)) {
                throw new \InvalidArgumentException("Parâmetro identificador de estado não informado!");
            }

            try {
                $apiResponse = $client->request("GET", LOCATION_PUBLIC_API . "/estados/{$stateId}/municipios?orderBy=nome");
            } catch (RequestException $e) {
                throw new \Exception("Serviço de consulta indisponível no momento, tente novamente mais tarde!");
            }

            $cities = json_decode($apiResponse->getBody()->getContents(), true);

            $cities = array_map(function ($city) {
                return [
                    "id" => $city["id"],
                    "name" => $city["nome"]
                ];
            }, $cities);

            $response = $response->withStatus(200)->withJson([
                "data" => $cities
            ]);

        } catch (\InvalidArgumentException $e) {
            $response = $response->withStatus(400)->withJson([
                "message" => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $response = $response->withStatus(500)->withJson([
                "message" => $e->getMessage()
            ]);
        }

        return $response;
    }

    public function getLocationByCep(Request $request, Response $response, array $args)
    {
        $client = new Client();

        $cep = $args["cep"];
        try {
            if (empty ($cep)) {
                throw new \InvalidArgumentException("Parâmetro cep não informado!");
            }

            try {
                $apiResponse = $client->request("GET", CEP_PUBLIC_API . "/{$cep}");
            } catch (RequestException $e) {
                if ($e->getResponse()->getStatusCode() == 404) {
                    throw new \InvalidArgumentException("Cep informado não foi encontrado, preecha os dados manualmente, ou tente novamente com outro!");
                }
                throw new \Exception("Serviço de consulta indisponível no momento, tente novamente mais tarde!");
            }

            $location = json_decode($apiResponse->getBody()->getContents(), true)["result"];

            $location = [
                "address" => ($location["street"] . ", " . $location["district"]),
                "state" => $location["state"],
                "city" => $location["city"]
            ];

            $response = $response->withStatus(200)->withJson([
                "data" => $location
            ]);


        } catch (\InvalidArgumentException $e) {
            $response = $response->withStatus(400)->withJson([
                "message" => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $response = $response->withStatus(500)->withJson([
                "message" => $e->getMessage()
            ]);
        }

        return $response;
    }
}