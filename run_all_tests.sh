#!/bin/bash
echo "======================================================="
echo "Running all default Granada tests..."
echo "======================================================="
phpunit

echo "======================================================="
echo "Running all default Idiorm tests..."
echo "======================================================="
phpunit -c phpunit-idiorm.xml

echo "======================================================="
echo "Running all Eager Granada tests..."
echo "======================================================="
phpunit -c phpunit-granada-eager.xml