from quantum_random import QuantumRandomGenerator

qrng = QuantumRandomGenerator()

def process_request(req_list):
    result = []

    for entry in req_list:
        cfg = entry["random"]

        r_type = cfg["type"]
        count = cfg["count"]
        unique = cfg["unique"]
        rng_range = cfg.get("range")
        alphabet = cfg.get("alphabet")

        if r_type == "int":
            nums = []
            while len(nums) < count:
                n = qrng.random_int(rng_range[0], rng_range[1])
                if not unique or n not in nums:
                    nums.append(n)
            result.append(nums)

        elif r_type == "char":
            chars = qrng.random_chars(alphabet, count)
            result.append(chars)

    return result

