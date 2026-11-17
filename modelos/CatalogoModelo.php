<?php
// Modelo: las listas para rellenar los desplegables
class CatalogoModelo
{
    public function __construct(private PDO $pdo) {}

    public function vehiculos(): array
    {
        // TODO 1: id, matricula, marca y modelo de cada vehículo, con el nombre
        //         y los apellidos de su cliente; ordenados por marca y modelo
        return $this->pdo->query(
            "SELECT v.id, v.matricula, v.marca, v.modelo, c.nombre, c.apellidos
             FROM vehiculo v
             JOIN cliente c ON v.id_cliente = c.id
             ORDER BY v.marca, v.modelo"
        )->fetchAll();
    }

    public function mecanicos(): array
    {
        // TODO 2: id, nombre y especialidad de cada mecánico, ordenados por nombre
        return $this->pdo->query(
            "SELECT id, nombre, especialidad
             FROM mecanico
             ORDER BY nombre"
        )->fetchAll();
    }

    // Todas las piezas del almacén, ordenadas por nombre
    public function piezas(): array
    {
        return $this->pdo->query(
            "SELECT id, nombre_pieza AS nombre, numero_referencia AS referencia, precio
             FROM piezas
             ORDER BY nombre_pieza"
        )->fetchAll();
    }
}