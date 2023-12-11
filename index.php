<?php

class CircuitBreaker{
    private $estado = 'fechado';
    private $limiteFalhas = 3;
    private $intervaloReset = 5; // segundos
    private $contagemFalhas = 0;
    private $tempoUltimaFalha = null;

    public function executar(callable $operacao){
        if($this->estado === 'aberto' && $this->intervaloFoiAlcancado()){
            $this->estado = 'meio-aberto';
        }

        if($this->estado === 'fechado' || $this->estado === 'meio-aberto') {
            try{
                $resultado = $operacao();
                $this->reset();
                return $resultado;
            } catch(Exception $e){
                $this->computarFalha();
                throw $e;
            }
        }

        throw new CircuitBreakerAbertoException("Circuit breaker está aberto.");
    }

    private function computarFalha(){
        $this->contagemFalhas++;
        $this->tempoUltimaFalha = time();

        if ($this->contagemFalhas >= $this->limiteFalhas){
            $this->estado = 'aberto';
        }
    }

    private function intervaloFoiAlcancado() {
        return time() - $this->tempoUltimaFalha >= $this->intervaloReset;
    }

    private function reset() {
        $this->contagemFalhas = 0;
        $this->tempoUltimaFalha = null;
        $this->estado = 'fechado';
    }
}

class CircuitBreakerAbertoException extends Exception{}

// Usage
$circuitBreaker = new CircuitBreaker();
for($i = 1; $i <= 30; $i++){
    try {
        $resultado = $circuitBreaker->executar(function (){
            /* Aqui se pode inserir qualquer serviço
            ou operação, como uma requisição HTTP ou
            chamar uma API. */

            $numeroAleatorio = rand(1, 10);
            
            /* Como um exemplo, ao gerar um número aleatório entre 1 a 10 maior que 5
            é considerado com um fracasso, de certa forma simulando requisições em uma conexão instável */
            if($numeroAleatorio > 5){
                throw new Exception("Chamada de serviço falhou.");
            }

            return "Chamada de serviço bem-sucedida.";
        });

        echo $resultado;
    } catch (CircuitBreakerAbertoException $e) {
        echo "Circuit breaker está aberto, por favor, tente novamente mais tarde.";
    } catch (Exception $e) {
        echo "Um erro ocorreu: " . $e->getMessage();
    }
    echo "<br>";
    sleep(1);
}

?>