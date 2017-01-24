<?php 
namespace Vendor\Ext\Finisher;

use In2code\Powermail\Domain\Model\Mail;
use In2code\Powermail\Finisher\AbstractFinisher;
use \TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use Vendor\Ext\Domain\Model\ModelName;  



/**
 * Class DoSomethingFinisher
 *
 * @package Vendor\Ext\Finisher
 */
class AddImageFinisher extends AbstractFinisher
{
	 /**
	 * @var Mail
	 */
	protected $mail;
   
	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * Will be called always at first
	 *
	 * @return void
	 */
	public function initializeFinisher()
	{
	}

	/**
	 * RepositoryName
	 *
	 * @var Vendor\Ext\Domain\Repository\RepositoryName
	 * @inject
	 */
	protected $RepositoryName = NULL;

	/**
	 * Will be called before myFinisher()
	 *
	 * @return void
	 */
	public function initializeMyFinisher()
	{
		
	}

	 /**
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param int $resourcePointer
	 * @return \Helhum\UploadExample\Domain\Model\FileReference
	 */
	protected function createFileReferenceFromFalFileObject(\TYPO3\CMS\Core\Resource\File $file, $resourcePointer = null, $obj)
	{
		$fileObject = $this->resourceFactory->getFileObject($file->getUid());
		$newId = uniqid('NEW_');
        $data = [];
        $data['sys_file_reference'][$newId] = [
            'table_local' => 'sys_file',
            'uid_local' => $fileObject->getUid(),
            'tablenames' => 'Third party table',
            'uid_foreign' => $obj->getUid(),
            'fieldname' => 'image',
            'pid' => $this->configuration['pid']['value'],
        ];
        $data['Third party table'][$obj->getUid()] = [
            'image' => $newId,
        ];
        // Get an instance of the DataHandler and process the data
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(
        	'TYPO3\CMS\Core\DataHandling\DataHandler'
        );
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (count($dataHandler->errorLog) !== 0) {
            echo 'error !!!';
            exit;
        }
	}

	/**
	 *
	 * @var array $fileData
	 *
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 */
	private function uploadFile($fileData, $targetNameOfFile, $obj) {
		$storageRepository =
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storage = $storageRepository->findByUid(1); # Fileadmin = 1
		$saveFolder = $storage->getFolder($this->settings['uploadFolder']);
		$fileObject = $storage->addFile($fileData, $saveFolder, $targetNameOfFile);
		$repositoryFileObject = $storage->getFile($fileObject->getIdentifier());
		$this->createFileReferenceFromFalFileObject($repositoryFileObject, null, $obj);
		return $repositoryFileObject;
	}

	/**
	 * MyFinisher
	 *
	 * @return void
	 */
	public function myFinisher()
	{
		$path = PATH_site . $this->settings['misc']['file']['folder'];
		$fields = $this->getMail()->getAnswers();
		$obj = GeneralUtility::makeInstance(
			'Vendor\Ext\Domain\Model\ModelName'
		);
		
		if ($fields->count()) {
			//Get Powermail Fields Title
			$name = $this->configuration['field_name'];
			$place = $this->configuration['field_place'];
			$email = $this->configuration['field_email'];
			$message = $this->configuration['field_message'];

			$resultFile = '';
			foreach ($fields as $field) {
				$customerName = '';
				$customerPlace = '';
				$customerEmail = '';
				$customerMessage = '';
				if ($field->getField()->getTitle() == $name) {
					$customerName = $field->getValue();
					$obj->setName($customerName);
				}
				if ($field->getField()->getTitle() == $place) {
					$customerPhone = $field->getValue();
					$obj->setPlace($customerPhone);
				}
				if ($field->getField()->getTitle() == $email) {
					$customerEmail = $field->getValue();
					$obj->setEmail($customerEmail);

				}
				if ($field->getField()->getTitle() == $message) {
					$customerMessage = $field->getValue();
					$obj->setMessage($customerMessage);
				}
				if ($field->getValueType() == 3) {
					if (is_array($field->getValue())) {
						$files = $field->getValue();
						foreach ($files as $file) {
							$fileRes = $path . $file;  
							$resultFile = $file;
						}
					}   
				}   
			}
		}
		
		$this->persistenceManager->add($obj);
		$this->persistenceManager->persistAll();
		$this->uploadFile($fileRes, $resultFile, $obj);
	}
}
