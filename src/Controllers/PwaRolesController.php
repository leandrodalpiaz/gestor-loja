<?php

namespace App\Controllers;

class PwaRolesController
{
    public function veneravel(): void
    {
        (new VeneravelController())->index();
    }

    public function orador(): void
    {
        (new OradorController())->index();
    }

    public function banquetes(): void
    {
        (new MestreBanquetesController())->index();
    }

    public function banquetesSalvarOperacao(): void
    {
        (new MestreBanquetesController())->salvarOperacao();
    }

    public function harmonia(): void
    {
        (new MestreHarmoniaController())->index();
    }

    public function hospitaleiro(): void
    {
        (new HospitaleiroController())->index();
    }

    public function hospitaleiroSalvarOcorrencia(): void
    {
        (new HospitaleiroController())->salvarOcorrencia();
    }

    public function hospitaleiroAtualizarStatus(): void
    {
        (new HospitaleiroController())->atualizarStatusOcorrencia();
    }

    public function hospitaleiroRegistrarVisita(): void
    {
        (new HospitaleiroController())->registrarVisita();
    }

    public function primeiroVigilante(): void
    {
        (new PrimeiroVigilanteController())->index();
    }

    public function segundoVigilante(): void
    {
        (new SegundoVigilanteController())->index();
    }
}
