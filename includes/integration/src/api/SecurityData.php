<?php
// phpcs:ignoreFile -- this is not a core file
namespace WTEHBL\Payment;

class SecurityData {

	/**
	 * JWE Key Id.
	 *
	 * @var string
	 */
	public static string $EncryptionKeyId = '';

	/**
	 * Access Token.
	 *
	 * @var string
	 */
	public static string $AccessToken = '';

	/**
	 * Token Type - Used in JWS and JWE header.
	 *
	 * @var string
	 */
	public static string $TokenType = 'JWT';

	/**
	 * JWS (JSON Web Signature) Signature Algorithm - This parameter identifies the cryptographic algorithm used to
	 * secure the JWS.
	 *
	 * @var string
	 */
	public static string $JWSAlgorithm = 'PS256';

	/**
	 * JWE (JSON Web Encryption) Key Encryption Algorithm - This parameter identifies the cryptographic algorithm
	 * used to secure the JWE.
	 *
	 * @var string
	 */
	public static string $JWEAlgorithm = 'RSA-OAEP';

	/**
	 * JWE (JSON Web Encryption) Content Encryption Algorithm - This parameter identifies the content encryption
	 * algorithm used on the plaintext to produce the encrypted ciphertext.
	 *
	 * @var string
	 */
	public static string $JWEEncrptionAlgorithm = 'A128CBC-HS256';

}
