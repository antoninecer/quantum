import cirq

class QuantumRandomGenerator:
    def __init__(self):
        self.sim = cirq.Simulator()
        self.q = cirq.LineQubit(0)

    def random_bit(self):
        circuit = cirq.Circuit(
            cirq.H(self.q),
            cirq.measure(self.q, key="m")
        )
        res = self.sim.run(circuit, repetitions=1)
        return int(res.measurements["m"][0][0])

    def random_int(self, min_val, max_val):
        range_size = max_val - min_val + 1
        bits_needed = (range_size - 1).bit_length()

        num = 0
        for _ in range(bits_needed):
            num = (num << 1) | self.random_bit()

        return min_val + (num % range_size)

    def random_chars(self, alphabet, count):
        return [alphabet[self.random_int(0, len(alphabet) - 1)] for _ in range(count)]

